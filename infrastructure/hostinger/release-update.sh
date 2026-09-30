#!/usr/bin/env bash
set -Eeuo pipefail
umask 027
root="$HOME/apps/salada-mix-hml"
web="$HOME/domains/ivory-rook-276202.hostingersite.com/public_html"
current="$root/current"
shared="$root/shared"
sha="$1"; runid="$2"
[[ "$sha" =~ ^[0-9a-f]{40}$ && "$runid" =~ ^[0-9]+$ ]] || exit 2
mkdir -p "$root/releases" "$root/backups"
exec 9>"$root/.release.lock"
flock -n 9 || { echo 'Concurrent release blocked.' >&2; exit 3; }
[[ -d "$current" && ! -L "$current" && -s "$current/vendor/autoload.php" ]]
[[ -f "$shared/.env" && ! -L "$shared/.env" && -d "$web" && ! -L "$web" && -f "$web/index.php" ]]
[[ -s "$root/deployed_commit.txt" && ! -e "$root/.pending-release" ]]
oldsha="$(cat "$root/deployed_commit.txt")"
[[ "$oldsha" =~ ^[0-9a-f]{40}$ ]]
if [[ "$oldsha" == "$sha" ]]; then echo 'Already deployed; no changes needed.'; exit 0; fi
stage="$root/releases/$sha"
prev="$root/releases/previous-$oldsha-$runid"
backup="$root/backups/release-$runid"
[[ ! -e "$stage" && ! -e "$prev" && ! -e "$backup" && -s "$root/incoming/release-$sha.tar.gz" ]]
dump=""
if command -v mariadb-dump >/dev/null 2>&1; then dump="$(command -v mariadb-dump)"
elif command -v mysqldump >/dev/null 2>&1; then dump="$(command -v mysqldump)"
else echo 'Database dump tool missing; deployment blocked.' >&2; exit 4; fi
mkdir -p "$stage" "$backup/web"
tar -xzf "$root/incoming/release-$sha.tar.gz" -C "$stage" --no-same-owner
[[ -f "$stage/artisan" && -s "$stage/vendor/autoload.php" && -s "$stage/public/build/manifest.json" && ! -e "$stage/.env" ]]
cp -p "$shared/.env" "$stage/.env"; chmod 600 "$stage/.env"
mkdir -p "$stage/bootstrap/cache" "$stage/storage/framework/cache/data" "$stage/storage/framework/sessions" "$stage/storage/framework/views" "$stage/storage/logs"
chmod -R u+rwX "$stage/bootstrap/cache" "$stage/storage"
# Hostinger Apache/PHP must traverse/read the private release via account group.
# Tar extraction under umask 077 created mode 700/600 and returned HTTP 403.
find "$stage" -type d -exec chmod 750 {} +
find "$stage" -type f -exec chmod 640 {} +
chmod 600 "$stage/.env"
chmod 750 "$root" "$shared" "$root/releases"
cd "$stage"
php artisan package:discover --no-ansi
php artisan config:clear --no-ansi
php artisan route:list --json >/dev/null
php -r '
require "vendor/autoload.php"; $app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config("app.env") !== "staging" || config("app.debug")
 || config("marketplace.checkout_enabled") || config("marketplace.order_drafts_enabled")
 || config("marketplace.payments_provider") !== "none") exit(5);
Illuminate\Support\Facades\DB::selectOne("SELECT 1");
echo "HML settings and database: OK\n";
'
cp -a "$web/." "$backup/web/"
php -r '
$env=file_get_contents($argv[1]);
if (!preg_match("/^DB_PASSWORD_B64=([A-Za-z0-9+\/=]+)$/m", $env, $m)) exit(7);
$pw=base64_decode($m[1], true);
if ($pw===false || $pw==="") exit(7);
echo "[client]\nhost=localhost\nuser=u904890479_sm_hml\npassword=\"".addcslashes($pw, "\\\"")."\"\n";
' "$shared/.env" > "$backup/client.cnf"
chmod 600 "$backup/client.cnf"
"$dump" --defaults-extra-file="$backup/client.cnf" --single-transaction --quick --no-tablespaces u904890479_salada_hml > "$backup/database.sql"
[[ -s "$backup/database.sql" ]]
rm -f "$backup/client.cnf"
chmod 600 "$backup/database.sql"
echo 'Database and webroot backups completed.'
cutover=0
on_error() {
  status="$?"
  trap - ERR
  set +e
  if [[ "$cutover" == 1 ]]; then
    [[ ! -d "$current" ]] || mv "$current" "$root/releases/failed-$sha-$runid"
    [[ ! -d "$prev" ]] || mv "$prev" "$current"
    cp -a "$backup/web/." "$web/"
    find "$web" -type d -exec chmod 755 {} +
    find "$web" -type f -exec chmod 644 {} +
    printf '%s\n' "$oldsha" > "$root/deployed_commit.txt"
  fi
  [[ ! -d "$current" ]] || (cd "$current" && php artisan up --no-ansi)
  rm -f "$root/.pending-release" "$backup/client.cnf"
  echo 'Release failed; web code restored where necessary. Database backup kept; migrations not reversed.' >&2
  exit "$status"
}
trap on_error ERR
cd "$current"
php artisan down --retry=60 --no-ansi
if [[ ! -L "$current/storage" ]]; then
  [[ -d "$current/storage" && ! -e "$shared/storage" ]]
  mkdir -p "$shared/storage"
  cp -a "$current/storage/." "$shared/storage/"
  mv "$current/storage" "$current/storage.before-shared-$runid"
  ln -s "$shared/storage" "$current/storage"
else
  [[ "$(readlink -f "$current/storage")" == "$(readlink -f "$shared/storage")" ]]
fi
rm -rf "$stage/storage"; ln -s "$shared/storage" "$stage/storage"
cd "$stage"
php artisan migrate --force --no-interaction --no-ansi
php artisan migrate:status --no-ansi >/dev/null
# Seed only the isolated, non-commercial HML after backup; the seeder refuses
# checkout/payment flags and refuses to mix synthetic with genuine merchant/order data.
php artisan db:seed --class=MarketplaceDemoSeeder --force --no-ansi
php -r '
require "vendor/autoload.php"; $app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$s=Illuminate\Support\Facades\DB::table("sellers")->where("trade_name", "like", "%(DEMO)")->count();
$p=Illuminate\Support\Facades\DB::table("products")->where("slug", "like", "demo-%")->count();
if ($s !== 3 || $p < 9) exit(8);
echo "DEMO_DB_OK sellers=$s products=$p\n";
'

# Do not cache absolute storage/log paths until the release lives at /current.
php artisan route:list --json >/dev/null
printf '%s\n' "$sha" "$oldsha" "$prev" "$backup" > "$root/.pending-release"
mv "$current" "$prev"; cutover=1
mv "$stage" "$current"
# Laravel config cache embeds absolute paths (including logging and file cache).
# Generating it in /releases/SHA and moving that directory produced HTTP 500.
(cd "$current" && php artisan config:clear --no-ansi && php artisan config:cache --no-ansi && php artisan view:clear --no-ansi && php artisan view:cache --no-ansi && php artisan route:list --json >/dev/null)
find "$current/public" -mindepth 1 -maxdepth 1 ! -name index.php ! -name .htaccess ! -name robots.txt -exec cp -a {} "$web/" \;
find "$web" -type d -exec chmod 755 {} +
find "$web" -type f -exec chmod 644 {} +
cp "$current/infrastructure/hostinger/hml-index.php" "$web/index.php"
cp "$current/public/.htaccess" "$web/.htaccess"
cat >> "$web/.htaccess" <<'HTACCESS'

DirectoryIndex index.php
<IfModule mod_headers.c>
    Header always set X-Robots-Tag "noindex, nofollow, noarchive"
    Header always set Cache-Control "no-store, max-age=0"
</IfModule>
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
HTACCESS
printf 'User-agent: *\nDisallow: /\n' > "$web/robots.txt"
chmod 644 "$web/index.php" "$web/.htaccess" "$web/robots.txt"
printf '%s\n' "$sha" > "$root/deployed_commit.txt"
(cd "$current" && php artisan up --no-ansi)
trap - ERR
echo "HML release awaiting HTTP validation: $sha"
