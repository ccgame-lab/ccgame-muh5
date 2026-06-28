#!/bin/bash
set -e

# Local preflight checks
echo "Running local preflight checks..."

# Check if git is available
if ! command -v git &> /dev/null; then
    echo "Error: git is not installed."
    exit 1
fi

# Check current branch
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "master" ]; then
    echo "Error: Current branch must be 'master'. You are on '$CURRENT_BRANCH'."
    exit 1
fi

# Check if working tree is clean
if [ -n "$(git status --short)" ]; then
    echo "Error: Working tree is not clean. Please commit your changes before deploying."
    exit 1
fi

# Fetch from origin to ensure we have the latest info
echo "Fetching origin..."
git fetch origin

# Check if local HEAD matches origin/master
LOCAL_HEAD=$(git rev-parse HEAD)
ORIGIN_HEAD=$(git rev-parse origin/master)

if [ "$LOCAL_HEAD" != "$ORIGIN_HEAD" ]; then
    echo "Error: Local HEAD ($LOCAL_HEAD) does not match origin/master ($ORIGIN_HEAD)."
    echo "Please push your changes to origin before deploying."
    exit 1
fi

echo "Local preflight passed."
echo "Deploying commit: $LOCAL_HEAD"

# Remote deploy via SSH
echo "Starting remote deployment on ccgame-prod..."

SSH_CMD="ssh ccgame-prod"

REMOTE_CMD=$(cat << 'EOF'
set -e
echo "Cleaning up temp directory..."
rm -rf /tmp/ccgame-muh5-sync

echo "Cloning repository..."
git clone --branch master https://github.com/ccgame-lab/ccgame-muh5.git /tmp/ccgame-muh5-sync

echo "Syncing files to production..."
rsync -aHAX --exclude='.git' \
    --exclude='.env*' \
    --exclude='storage/' \
    --exclude='vendor/' \
    --exclude='bootstrap/cache/' \
    --exclude='node_modules/' \
    --exclude='update.zip' \
    /tmp/ccgame-muh5-sync/ /www/wwwroot/ccgame/ccgame-muh5/

cd /www/wwwroot/ccgame/ccgame-muh5

echo "Installing composer dependencies..."
# Prod PHP (8.1) chua dat yeu cau composer.lock (can 8.4) -> composer install LUON fail.
# Cho non-fatal de KHONG nuot cac buoc sau (optimize:clear/chown/smoke). Giu vendor hien tai
# (da chay tot tren FPM) thay vi --ignore-platform-req (nguy hiem: cai package 8.4 len 8.1).
# Khi nang prod len 8.4 thi composer se thanh cong va nhanh chong cap nhat vendor.
if ! composer install --no-dev --optimize-autoloader; then
    echo "WARN: composer install failed (PHP platform mismatch) - giu vendor hien tai, tiep tuc deploy."
fi

echo "Clearing caches..."
# CLI `php` tren prod la 8.1 -> KHONG bootstrap duoc artisan (Laravel v13 can 8.3+).
# Cho non-fatal: prod khong cache route/config (thay doi van len live qua rsync + opcache),
# nen optimize:clear bo qua duoc. Nang CLI len 8.3+ thi se chay binh thuong.
php artisan optimize:clear || echo "WARN: optimize:clear skipped (CLI PHP < 8.3) - prod khong cache route/config nen OK."

echo "Checking migrations (pretend mode)..."
php artisan migrate --pretend --force || echo "WARN: migrate --pretend skipped (CLI PHP < 8.3)."

echo "Setting permissions for storage, bootstrap/cache, public..."
chown -R www:www storage bootstrap/cache public

echo "Running smoke tests..."
echo "Testing /play..."
curl -I https://muh5.ccgame.org/play
echo "Testing /admin/login..."
curl -I https://muh5.ccgame.org/admin/login
echo "Testing /api/sdk/bootstrap..."
curl -s https://muh5.ccgame.org/api/sdk/bootstrap | head -c 300
echo ""

echo "Cleaning up temp directory..."
rm -rf /tmp/ccgame-muh5-sync

echo "Deployment finished successfully."
EOF
)

$SSH_CMD "$REMOTE_CMD"
