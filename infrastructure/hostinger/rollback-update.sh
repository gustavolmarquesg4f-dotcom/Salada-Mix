#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
root="$HOME/apps/salada-mix-hml"
web="$HOME/domains/ivory-rook-276202.hostingersite.com/public_html"
sha="$1"; runid="$2"
[[ "$sha" =~ ^[0-9a-f]{40}$ && "$runid" =~ ^[0-9]+$ ]]
exec 9>"$root/.release.lock"
flock -n 9 || { echo 'Another HML deployment is running.' >&2; exit 3; }
state="$root/.pending-release"
[[ -s "$state" ]] || { echo 'No pending release to roll back.'; exit 0; }
state_sha="$(sed -n '1p' "$state")"
oldsha="$(sed -n '2p' "$state")"
prev="$(sed -n '3p' "$state")"
backup="$(sed -n '4p' "$state")"
[[ "$state_sha" == "$sha" && "$oldsha" =~ ^[0-9a-f]{40}$ ]]
[[ "$prev" == "$root/releases/previous-$oldsha-$runid" && "$backup" == "$root/backups/release-$runid" ]]
[[ -d "$prev" && -f "$backup/web/index.php" && -s "$backup/database.sql" ]]
[[ -d "$root/current" && -f "$root/current/artisan" && -d "$web" ]]
mv "$root/current" "$root/releases/failed-$sha-$runid"
mv "$prev" "$root/current"
cp -a "$backup/web/." "$web/"
find "$web" -type d -exec chmod 755 {} +
find "$web" -type f -exec chmod 644 {} +
printf '%s\n' "$oldsha" > "$root/deployed_commit.txt"
(cd "$root/current" && php artisan up --no-ansi)
rm -f "$state"
echo "HML previous web release restored: $oldsha. Database migrations are forward-only; SQL backup retained."
