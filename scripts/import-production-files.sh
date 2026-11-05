#!/usr/bin/env bash
# make import-production-files: copies production's uploaded files (Fly volume:
# storage/app/public and storage/app/private) into backend/storage/app.
# Local files with the same names are overwritten; others are kept.
set -euo pipefail

APP="${FLY_APP:-cms-platform}"
REMOTE="/tmp/webnova-storage-$$.tgz"
LOCAL="$(mktemp -d)/storage.tgz"

command -v fly >/dev/null || { echo "fly is not installed"; exit 1; }
echo ""
echo "  This copies production's uploaded files, including members' private documents, to this computer."
read -r -p "  Type yes to continue: " answer
[ "$answer" = "yes" ] || { echo "Cancelled."; exit 1; }

echo "→ Packing the files on the server…"
fly ssh console -a "$APP" -q -C "tar czf $REMOTE -C /var/www/html/storage/app public private"
echo "→ Downloading…"
fly ssh sftp get -a "$APP" "$REMOTE" "$LOCAL"
fly ssh console -a "$APP" -q -C "rm -f $REMOTE" || true

echo "→ Unpacking into backend/storage/app…"
tar xzf "$LOCAL" -C backend/storage/app
rm -f "$LOCAL"
docker compose -f docker-compose.local.yml exec -T app php artisan storage:link >/dev/null 2>&1 || true
echo "Done ($(du -sh backend/storage/app/public | cut -f1) in public)."
