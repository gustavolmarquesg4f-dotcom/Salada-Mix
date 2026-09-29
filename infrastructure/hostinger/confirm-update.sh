#!/usr/bin/env bash
set -Eeuo pipefail
root="$HOME/apps/salada-mix-hml"
sha="$1"; runid="$2"
[[ "$sha" =~ ^[0-9a-f]{40}$ && "$runid" =~ ^[0-9]+$ ]]
exec 9>"$root/.release.lock"
flock -n 9 || exit 3
[[ "$(cat "$root/deployed_commit.txt")" == "$sha" ]]
state="$root/.pending-release"
if [[ -s "$state" ]]; then
  state_sha="$(sed -n '1p' "$state")"
  backup="$(sed -n '4p' "$state")"
  [[ "$state_sha" == "$sha" && "$backup" == "$root/backups/release-$runid" ]]
  rm -f "$state"
fi
echo "HML release confirmed: $sha"
