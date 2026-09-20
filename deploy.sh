#!/usr/bin/env bash
set -euo pipefail

# Deploy ALF Laravel app to Hostinger (pt.alfajarlogic.com)
#
# Usage: ./deploy.sh [--migrate]
#   --migrate   also run `php artisan migrate --force` on the server

SSH_KEY="$HOME/.ssh/hostinger_deploy"
SSH_PORT=65002
SSH_USER="u627878615"
SSH_HOST="46.202.138.72"
REMOTE_APP_DIR="domains/pt.alfajarlogic.com/laravel_app"
REMOTE_PUBLIC_HTML="domains/pt.alfajarlogic.com/public_html"
REMOTE_PHP="/opt/alt/php84/usr/bin/php"

SSH="ssh -i $SSH_KEY -p $SSH_PORT $SSH_USER@$SSH_HOST"
RSYNC_RSH="ssh -i $SSH_KEY -p $SSH_PORT"

RUN_MIGRATE=false
if [[ "${1:-}" == "--migrate" ]]; then
  RUN_MIGRATE=true
fi

cd "$(dirname "$0")"

echo "==> Building frontend assets"
npm run build

echo "==> Syncing application files to server"
rsync -avz --delete \
  -e "$RSYNC_RSH" \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='_legacy_site' \
  --exclude='.env' \
  --exclude='vendor' \
  --exclude='.phpunit.result.cache' \
  --exclude='tests' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/data/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  ./ "$SSH_USER@$SSH_HOST:$REMOTE_APP_DIR/"

echo "==> Syncing public/ assets to public_html"
rsync -avz \
  -e "$RSYNC_RSH" \
  --exclude='.htaccess' \
  --exclude='storage' \
  --exclude='index.php' \
  public/ "$SSH_USER@$SSH_HOST:$REMOTE_PUBLIC_HTML/"

echo "==> Installing composer dependencies on server"
$SSH "cd $REMOTE_APP_DIR && $REMOTE_PHP \$(which composer) install --no-dev --optimize-autoloader --no-interaction"

if $RUN_MIGRATE; then
  echo "==> Running database migrations"
  $SSH "cd $REMOTE_APP_DIR && $REMOTE_PHP artisan migrate --force"
fi

echo "==> Ensuring roles and permissions exist (idempotent)"
$SSH "cd $REMOTE_APP_DIR && $REMOTE_PHP artisan db:seed --class=RolePermissionSeeder --force"

echo "==> Clearing and rebuilding caches"
$SSH "cd $REMOTE_APP_DIR && $REMOTE_PHP artisan config:clear && $REMOTE_PHP artisan route:clear && $REMOTE_PHP artisan view:clear && $REMOTE_PHP artisan config:cache && $REMOTE_PHP artisan route:cache && $REMOTE_PHP artisan view:cache"

echo "==> Fixing storage/bootstrap cache permissions"
$SSH "cd $REMOTE_APP_DIR && chmod -R 775 storage bootstrap/cache"

echo "==> Done. https://pt.alfajarlogic.com"
