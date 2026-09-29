#!/usr/bin/env bash
set -Eeuo pipefail
umask 027

root="$HOME/apps/salada-mix-hml"
web="$HOME/domains/ivory-rook-276202.hostingersite.com/public_html"
backup="$root/backups/initial-public-html"
activated=0

rollback_partial() {
  if [[ "$activated" == 1 ]]; then
    cp -p "$backup/default.php" "$web/default.php" || true
    rm -f "$web/index.php" "$web/.htaccess" "$web/robots.txt" "$root/deployed_commit.txt" || true
    rm -rf "$web/build" "$web/assets" || true
    chmod 755 "$web" || true
    echo 'A ativacao foi revertida para a pagina original da HML.' >&2
  fi
}
trap rollback_partial ERR

# This deliberately resumes the private installation left by the first deploy.
# It MUST NOT run an initial migration, recreate .env or replace an unknown webroot.
[[ -d "$root/current" && ! -L "$root/current" ]]
[[ -f "$root/current/artisan" && -f "$root/current/vendor/autoload.php" ]]
[[ -s "$root/current/public/build/manifest.json" ]]
[[ -f "$root/current/infrastructure/hostinger/hml-index.php" ]]
[[ -f "$root/shared/.env" && -f "$backup/default.php" ]]
[[ -d "$web" && ! -L "$web" ]]
[[ "$(stat -c %a "$web")" == 750 || "$(stat -c %a "$web")" == 755 ]]
[[ ! -e "$root/deployed_commit.txt" ]]
shopt -s nullglob dotglob
existing=("$web"/*)
if (( ${#existing[@]} != 1 )) || [[ "${existing[0]##*/}" != default.php ]]; then
  echo 'A raiz publica da HML nao esta no estado esperado. Nenhum arquivo alterado.' >&2
  exit 1
fi
cd "$root/current"
php artisan migrate:status --no-ansi > "$root/incoming/migration-status-check.txt"
if grep -qi pending "$root/incoming/migration-status-check.txt"; then
  echo 'Existem migrations pendentes. Nao ativar ate revisar a base.' >&2
  exit 1
fi
php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config("app.env") !== "staging"
  || config("marketplace.checkout_enabled")
  || config("marketplace.order_drafts_enabled")
  || config("marketplace.payments_provider") !== "none") {
    fwrite(STDERR, "Gates de seguranca da HML reprovados.\n");
    exit(45);
}
Illuminate\Support\Facades\DB::selectOne("SELECT 1");
echo "HML private Laravel + MariaDB: OK\n";
'
php artisan route:list --path=up --no-ansi > /dev/null

# Only public assets and the adjusted entrypoint leave the private app tree.
activated=1
cp -a "$root/current/public/." "$web/"
# cp -a SOURCE/. DEST/ also preserves SOURCE directory mode on DEST. The
# release was extracted under umask 027, so public may be mode 750; Apache
# cannot traverse that webroot. Restore only this HML document root to 755.
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
# The private application was built by the first deployment at this original revision;
# later changes up to this activation were only CI and diagnostic scripts.
printf '%s\n' 'dcada56276ba4e70db177082f479a5c30d6ae7f3' > "$root/deployed_commit.txt"
trap - ERR
printf 'Private Laravel activated into HML document root.\n'
