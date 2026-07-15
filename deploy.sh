#!/bin/bash
# Manual deploy script — run this on the server over SSH when you don't use
# cPanel Git Version Control. Usage:
#   ssh CPANEL_USER@planeticweb.com 'bash ~/planetic-web/deploy.sh'
# Adjust APP_DIR to wherever the git clone lives on the server.
set -euo pipefail

APP_DIR="${APP_DIR:-$HOME/planetic-web}"
PHP="/usr/local/bin/php"
COMPOSER="/opt/cpanel/composer/bin/composer"

cd "$APP_DIR"

echo "→ Pulling latest code"
git pull origin main

echo "→ Installing PHP dependencies"
"$PHP" "$COMPOSER" install --no-dev --optimize-autoloader --no-interaction

echo "→ Migrating database"
"$PHP" artisan migrate --force

echo "→ Rebuilding caches"
"$PHP" artisan optimize:clear
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache
"$PHP" artisan event:cache

echo "→ Syncing static assets to document root"
if [ -d "$HOME/public_html" ]; then
    [ ! -L "$HOME/public_html/build" ] && rsync -a --delete ./public/build/ "$HOME/public_html/build/"
    [ -d ./public/images ] && rsync -a ./public/images/ "$HOME/public_html/images/"
fi

echo "✓ Deployed $(git rev-parse --short HEAD)"
