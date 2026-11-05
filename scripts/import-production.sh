#!/usr/bin/env bash
# make import-production: copies the production databases (Fly) into the local
# Docker stack: the central database and every site's database (customer_*).
# Then `webnova:prepare-production-copy` re-encrypts settings with the local
# key, removes payment keys and switches the platform's mail server off, so
# the copy cannot take payments or send real email.
#
# Production's database address and app key are read with `fly ssh` and kept
# in memory only. Local data is REPLACED. Needs: fly (logged in), Docker.
set -euo pipefail

APP="${FLY_APP:-cms-platform}"
PORT="${FLY_DB_PORT:-15432}"
COMPOSE="docker compose -f docker-compose.local.yml"
LOCAL_CENTRAL="webnova_central"
LOCAL_USER="webnova"

command -v fly >/dev/null || { echo "fly is not installed (https://fly.io/docs/flyctl/install/)"; exit 1; }
$COMPOSE ps --status running postgres | grep -q postgres || { echo "Start the local stack first: make up"; exit 1; }

echo ""
echo "  This REPLACES your local databases (central and every site) with a copy of production."
echo "  The copy contains real members' personal data: keep it on this computer only."
read -r -p "  Type yes to continue: " answer
[ "$answer" = "yes" ] || { echo "Cancelled."; exit 1; }

echo "→ Reading production's database address and app key (fly ssh)…"
fly_env() { fly ssh console -a "$APP" -q -C "printenv $1" 2>/dev/null | tr -d '\r' | grep -E "$2" | tail -1 || true; }
DB_URL="$(fly_env DATABASE_URL '^postgres')"
PROD_KEY="$(fly_env APP_KEY '^base64:')"
[ -n "$DB_URL" ] || { echo "Could not read DATABASE_URL from $APP (is the app running? fly status -a $APP)"; exit 1; }
[ -n "$PROD_KEY" ] || { echo "Could not read APP_KEY from $APP"; exit 1; }

# postgres://user:password@host:port/dbname?…
read -r DB_USER DB_PASS DB_HOST DB_NAME < <(python3 - "$DB_URL" <<'PY'
import sys, urllib.parse as u
p = u.urlparse(sys.argv[1])
print(u.unquote(p.username or ''), u.unquote(p.password or ''), p.hostname or '', (p.path or '/').lstrip('/') or 'postgres')
PY
)
# The Postgres app's name: the first part of its Fly host name (cms-postgres.flycast → cms-postgres)
DB_APP="${FLY_DB_APP:-${DB_HOST%%.*}}"

echo "→ Opening a tunnel to $DB_APP on localhost:${PORT}…"
fly proxy "$PORT:5432" -a "$DB_APP" >/dev/null 2>&1 &
PROXY=$!
trap 'kill $PROXY 2>/dev/null || true' EXIT
for _ in $(seq 1 30); do nc -z 127.0.0.1 "$PORT" 2>/dev/null && break; sleep 1; done
nc -z 127.0.0.1 "$PORT" || { echo "The tunnel did not open."; exit 1; }

# Postgres tools in a throwaway container, the same version as production's server
pg() { docker run --rm -i --add-host=host.docker.internal:host-gateway -e PGPASSWORD="$DB_PASS" "$CLIENT" "$@"; }
CLIENT="postgres:16-alpine"
MAJOR="$(pg psql -h host.docker.internal -p "$PORT" -U "$DB_USER" -d "$DB_NAME" -Atc 'show server_version_num' | cut -c1-2)"
CLIENT="postgres:${MAJOR}-alpine"
echo "→ Production runs PostgreSQL $MAJOR"

DATABASES="$(pg psql -h host.docker.internal -p "$PORT" -U "$DB_USER" -d "$DB_NAME" -Atc \
  "select datname from pg_database where datname = '$DB_NAME' or datname like 'customer\_%' order by datname = '$DB_NAME' desc, datname")"

for DB in $DATABASES; do
  TARGET="$DB"; [ "$DB" = "$DB_NAME" ] && TARGET="$LOCAL_CENTRAL"
  echo "→ $DB → local $TARGET"
  $COMPOSE exec -T postgres psql -q -U "$LOCAL_USER" -d postgres \
    -c "DROP DATABASE IF EXISTS \"$TARGET\" WITH (FORCE)" -c "CREATE DATABASE \"$TARGET\" OWNER $LOCAL_USER" >/dev/null
  pg pg_dump -h host.docker.internal -p "$PORT" -U "$DB_USER" -d "$DB" --no-owner --no-privileges \
    | $COMPOSE exec -T postgres psql -q -U "$LOCAL_USER" -d "$TARGET" >/dev/null 2>/tmp/webnova-import-$TARGET.log \
    || echo "  (some statements failed: see /tmp/webnova-import-$TARGET.log)"
done

echo "→ Making the copy safe for local use…"
$COMPOSE exec -T -e PRODUCTION_APP_KEY="$PROD_KEY" app php artisan webnova:prepare-production-copy
# Local code may be newer than production's
$COMPOSE exec -T app php artisan migrate --force >/dev/null
$COMPOSE exec -T app php artisan tenants:migrate --force >/dev/null
$COMPOSE exec -T app php artisan cache:clear >/dev/null

echo ""
echo "Done. Admin logins are production's. Sites answer on their real domains (add them to /etc/hosts)"
echo "or on http://{site}.localhost:8080 . Uploaded files: make import-production-files"
