#!/usr/bin/env bash
set -euo pipefail

# Deploy ALF Laravel app to Hostinger (pt.alfajarlogic.com)
#
# How production works:
#   Hostinger Git auto-deploy clones `main` from GitHub into public_html and runs
#   `composer install`. It preserves .env, public/build and bootstrap/cache/*, and it does NOT
#   build Vite assets, run migrations or refresh caches. This script does those parts.
#
# Steps:
#   1. check that HEAD is committed and pushed, then wait until the server has pulled it
#   2. build the frontend locally and upload public/build
#   3. (--migrate) back up the MySQL database, then run migrations
#   4. seed roles, link storage, rebuild caches, fix permissions
#   5. smoke test the live site
#
# Usage: ./deploy.sh [--migrate]
#   --migrate   back up the database to ~/db_backups on the server, then run `php artisan migrate --force`

SSH_KEY="$HOME/.ssh/hostinger_deploy"
SSH_PORT=65002
SSH_USER="u627878615"
SSH_HOST="46.202.138.72"
REMOTE_APP_DIR="domains/pt.alfajarlogic.com/public_html"
REMOTE_PHP="/opt/alt/php84/usr/bin/php"
SITE_URL="https://pt.alfajarlogic.com"
WAIT_SECONDS=300

SSH="ssh -i $SSH_KEY -p $SSH_PORT -o BatchMode=yes $SSH_USER@$SSH_HOST"
RSYNC_RSH="ssh -i $SSH_KEY -p $SSH_PORT -o BatchMode=yes"

RUN_MIGRATE=false
if [[ "${1:-}" == "--migrate" ]]; then
  RUN_MIGRATE=true
fi

cd "$(dirname "$0")"

echo "==> Checking that this commit is on GitHub"
if ! git diff --quiet HEAD; then
  echo "Tracked files have uncommitted changes. Commit and push them first: production deploys from GitHub." >&2
  exit 1
fi
git fetch origin main --quiet
LOCAL_SHA="$(git rev-parse HEAD)"
if [[ "$LOCAL_SHA" != "$(git rev-parse origin/main)" ]]; then
  echo "HEAD ($LOCAL_SHA) is not the same as origin/main. Push (or pull) first." >&2
  exit 1
fi

echo "==> Waiting for Hostinger to pull ${LOCAL_SHA:0:7} (up to ${WAIT_SECONDS}s)"
waited=0
until [[ "$($SSH "git -C $REMOTE_APP_DIR rev-parse HEAD")" == "$LOCAL_SHA" ]]; do
  if (( waited >= WAIT_SECONDS )); then
    echo "The server did not pick up the commit. Trigger a deploy in hPanel > Git, then run this script again." >&2
    exit 1
  fi
  sleep 10
  waited=$((waited + 10))
done

echo "==> Building frontend assets"
npm run build

echo "==> Uploading public/build"
rsync -avz --delete -e "$RSYNC_RSH" public/build/ "$SSH_USER@$SSH_HOST:$REMOTE_APP_DIR/public/build/"

echo "==> Pending migrations"
$SSH "cd $REMOTE_APP_DIR && $REMOTE_PHP artisan migrate:status --pending"

if $RUN_MIGRATE; then
  echo "==> Backing up the database"
  $SSH "APP_DIR=$REMOTE_APP_DIR bash -s" <<'EOF'
set -euo pipefail
cd "$APP_DIR"
env_value() { grep -E "^$1=" .env | head -1 | cut -d= -f2- | tr -d '"'; }
mkdir -p ~/db_backups
chmod 700 ~/db_backups
file=~/db_backups/pre-deploy-$(date +%F-%H%M).sql
MYSQL_PWD="$(env_value DB_PASSWORD)" mysqldump -h "$(env_value DB_HOST)" -u "$(env_value DB_USERNAME)" \
  --single-transaction --no-tablespaces "$(env_value DB_DATABASE)" > "$file"
ls -la "$file"
EOF

  echo "==> Running database migrations"
  $SSH "cd $REMOTE_APP_DIR && $REMOTE_PHP artisan migrate --force"
fi

echo "==> Linking public storage (customer photos)"
$SSH "cd $REMOTE_APP_DIR && $REMOTE_PHP artisan storage:link || true"

echo "==> Ensuring roles and permissions exist (idempotent)"
$SSH "cd $REMOTE_APP_DIR && $REMOTE_PHP artisan db:seed --class=RolePermissionSeeder --force"

echo "==> Clearing and rebuilding caches"
$SSH "cd $REMOTE_APP_DIR && $REMOTE_PHP artisan optimize:clear && $REMOTE_PHP artisan config:cache && $REMOTE_PHP artisan route:cache && $REMOTE_PHP artisan view:cache"

echo "==> Fixing storage/bootstrap cache permissions"
$SSH "cd $REMOTE_APP_DIR && chmod -R 775 storage bootstrap/cache"

echo "==> Smoke test"
for path in / /login; do
  code="$(curl -sS -o /dev/null -w '%{http_code}' --max-time 30 "$SITE_URL$path")"
  echo "$path -> $code"
  if [[ "$code" != "200" ]]; then
    echo "Smoke test failed. Check: $SSH \"tail -n 30 $REMOTE_APP_DIR/storage/logs/laravel.log\"" >&2
    exit 1
  fi
done

echo "==> Done. $SITE_URL"
