#!/usr/bin/env bash
set -Eeuo pipefail
root="$HOME/apps/salada-mix-hml"
web="$HOME/domains/ivory-rook-276202.hostingersite.com/public_html"
[[ -f "$root/backups/initial-public-html/default.php" ]]
[[ -f "$root/deployed_commit.txt" ]]
cp -p "$root/backups/initial-public-html/default.php" "$web/default.php"
rm -f "$web/index.php" "$web/.htaccess" "$web/robots.txt"
rm -rf "$web/build" "$web/assets"
chmod 755 "$web"
rm -f "$root/deployed_commit.txt"
# No database DOWN migration: the private HML schema remains for diagnosis.
printf 'Original Hostinger landing page restored after unsuccessful HTTP checks.\n'
