#!/usr/bin/env bash
set -Eeuo pipefail
umask 027
activated=0
rollback_partial() {
  if [[ "$activated" == 1 ]]; then
    cp -p "$backup/default.php" "$web/default.php" || true
    rm -f "$web/index.php" "$web/.htaccess" "$web/robots.txt" || true
    rm -rf "$web/build" "$web/assets" || true
    chmod 755 "$web" || true
    echo 'Original web landing page restored after a deployment error.' >&2
  fi
}
trap rollback_partial ERR

root="$HOME/apps/salada-mix-hml"
domain="$HOME/domains/ivory-rook-276202.hostingersite.com"
web="$domain/public_html"
sha="$1"
if [[ ! "$sha" =~ ^[0-9a-f]{40}$ ]]; then
  echo 'Invalid commit identifier.' >&2
  exit 1
fi

# This is a first-install workflow, not an unattended release upgrade.
if [[ -e "$root/current" || -L "$root/current" ]]; then
  echo 'An application already exists. Refusing to overwrite it.' >&2
  exit 1
fi
if [[ ! -d "$web" || -L "$web" ]]; then
  echo 'Unexpected public_html state.' >&2
  exit 1
fi
shopt -s nullglob dotglob
original=("$web"/*)
if (( ${#original[@]} != 1 )) || [[ "${original[0]##*/}" != 'default.php' ]]; then
  echo 'The public web root contains unexpected files. No deployment.' >&2
  exit 1
fi
[[ -f "$root/shared/.env" && -s "$root/incoming/release.tar.gz" ]]
[[ "$(php -r 'echo PHP_VERSION_ID;')" -ge 80300 ]]

stage="$root/releases/$sha"
backup="$root/backups/initial-public-html"
mkdir -p "$root/releases" "$root/backups"
if [[ -e "$stage" ]]; then
  echo 'This release stage already exists; refusing overwrite.' >&2
  exit 1
fi
mkdir -p "$stage" "$backup"
cp -p "$web/default.php" "$backup/default.php"
tar -xzf "$root/incoming/release.tar.gz" -C "$stage" --no-same-owner
[[ -f "$stage/artisan" && -f "$stage/vendor/autoload.php" && -s "$stage/public/build/manifest.json" ]]
cp -p "$root/shared/.env" "$stage/.env"
chmod 600 "$stage/.env"
mkdir -p "$stage/storage/logs" "$stage/storage/framework/cache/data" "$stage/storage/framework/sessions" "$stage/storage/framework/views" "$stage/bootstrap/cache"
chmod -R u+rwX "$stage/storage" "$stage/bootstrap/cache"

cd "$stage"
php artisan package:discover --no-ansi
php artisan config:clear --no-ansi
# Fail closed if any table was introduced outside this initial deployment.
php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); $n = (int) Illuminate\Support\Facades\DB::selectOne("SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema = DATABASE()")->n; if ($n !== 0) { fwrite(STDERR, "HML database is not empty. Refusing initial migration.\n"); exit(42); } echo "Initial database is empty.\n";'
php artisan migrate --force --no-interaction --no-ansi
php artisan db:seed --force --no-interaction --no-ansi
php artisan migrate:status --no-ansi > /dev/null

mv "$stage" "$root/current"
cd "$root/current"
php artisan config:cache --no-ansi
php artisan view:cache --no-ansi
php artisan route:list --json > /dev/null

# Keep Hostinger's configured document root and only publish public/ files.
activated=1
cp -a "$root/current/public/." "$web/"
chmod 755 "$web"
cp "$root/current/infrastructure/hostinger/hml-index.php" "$web/index.php"
cat >> "$web/.htaccess" <<'HTACCESS'

DirectoryIndex index.php
<IfModule mod_headers.c>
    Header always set X-Robots-Tag "noindex, nofollow, noarchive"
</IfModule>
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
HTACCESS
printf 'User-agent: *\nDisallow: /\n' > "$web/robots.txt"
chmod 644 "$web/index.php" "$web/.htaccess" "$web/robots.txt"
rm -f "$web/default.php"
printf '%s\n' "$sha" > "$root/deployed_commit.txt"
printf 'Initial HML release installed: %s\n' "$sha"
