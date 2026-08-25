#!/bin/bash
# Manual deploy script — run this on the server over SSH when you don't use
# cPanel Git Version Control. Usage:
#   ssh CPANEL_USER@planeticweb.com 'bash ~/planetic-web/deploy.sh'
# Adjust APP_DIR to wherever the git clone lives on the server.
set -euo pipefail

APP_DIR="${APP_DIR:-$HOME/planeticweb-app}"
PHP="/usr/local/bin/php"

# Find composer wherever this host keeps it.
COMPOSER="${COMPOSER:-}"
if [ -z "$COMPOSER" ]; then
    for candidate in /opt/cpanel/composer/bin/composer /usr/local/bin/composer /usr/bin/composer "$HOME/composer.phar"; do
        [ -f "$candidate" ] && COMPOSER="$candidate" && break
    done
fi

cd "$APP_DIR"

echo "→ Pulling latest code"
git pull origin main

# Drop the compiled caches BEFORE anything else can fail. A cached config from
# a previous deploy does not know about config files added in this one, so a
# deploy that aborts midway would otherwise leave the app running on a stale
# config/route cache — which is far more broken than running uncached.
# Clearing first means the worst case is "slow but working".
echo "→ Clearing stale caches"
"$PHP" artisan optimize:clear

if [ -n "$COMPOSER" ] && [ -r "$COMPOSER" ]; then
    echo "→ Installing PHP dependencies ($COMPOSER)"
    # Non-fatal: no new dependency is required by most deploys, and a missing
    # composer must not abort the migration and cache steps below.
    "$PHP" "$COMPOSER" install --no-dev --optimize-autoloader --no-interaction \
        || echo "  ! composer install failed — continuing (only a problem if composer.json changed)"
else
    echo "→ Composer not found — skipping dependency install (fine unless composer.json changed)"
fi

echo "→ Migrating database"
"$PHP" artisan migrate --force

echo "→ Rebuilding caches"
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache
"$PHP" artisan event:cache

echo "→ Syncing static assets to document root"
if [ -d "$HOME/public_html" ]; then
    [ ! -L "$HOME/public_html/build" ] && rsync -a --delete ./public/build/ "$HOME/public_html/build/"
    [ -d ./public/images ] && rsync -a ./public/images/ "$HOME/public_html/images/"
    # Root-level static files (favicon set, robots.txt). public/ is never
    # rsynced wholesale — public_html's index.php/.htaccess were adjusted for
    # this host — so these are copied by name.
    cp -f ./public/favicon.ico ./public/favicon.svg ./public/apple-touch-icon.png ./public/robots.txt "$HOME/public_html/" 2>/dev/null || true
fi

echo "✓ Deployed $(git rev-parse --short HEAD)"
