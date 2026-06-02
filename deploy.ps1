$ErrorActionPreference = 'Stop'

Write-Host "Running local preflight checks..."

# Check current branch
$CurrentBranch = git branch --show-current
if ($CurrentBranch -ne "master") {
    Write-Error "Current branch must be 'master'. You are on '$CurrentBranch'."
    exit 1
}

# Check if working tree is clean
$Status = git status --short
if (-not [string]::IsNullOrWhiteSpace($Status)) {
    Write-Error "Working tree is not clean. Please commit your changes before deploying."
    exit 1
}

# Fetch origin
Write-Host "Fetching origin..."
git fetch origin

# Check if local HEAD matches origin/master
$LocalHead = git rev-parse HEAD
$OriginHead = git rev-parse origin/master

if ($LocalHead -ne $OriginHead) {
    Write-Error "Local HEAD ($LocalHead) does not match origin/master ($OriginHead). Please push your changes to origin before deploying."
    exit 1
}

Write-Host "Local preflight passed."
Write-Host "Deploying commit: $LocalHead"

# Remote deploy via SSH
Write-Host "Starting remote deployment on ccgame-prod..."

$RemoteCmd = @"
set -e
echo 'Cleaning up temp directory...'
rm -rf /tmp/ccgame-muh5-sync

echo 'Cloning repository...'
git clone --branch master https://github.com/ccgame-lab/ccgame-muh5.git /tmp/ccgame-muh5-sync

echo 'Syncing files to production...'
rsync -aHAX --exclude='.git' --exclude='.env*' --exclude='storage/' --exclude='vendor/' --exclude='bootstrap/cache/' --exclude='node_modules/' --exclude='update.zip' /tmp/ccgame-muh5-sync/ /www/wwwroot/ccgame/ccgame-muh5/

cd /www/wwwroot/ccgame/ccgame-muh5

echo 'Installing composer dependencies...'
composer install --no-dev --optimize-autoloader

echo 'Clearing caches...'
php artisan optimize:clear

echo 'Checking migrations pretend mode...'
php artisan migrate --pretend --force

echo 'Setting permissions for storage, bootstrap/cache, public...'
chown -R www:www storage bootstrap/cache public

echo 'Running smoke tests...'
echo 'Testing play...'
curl -I https://muh5.ccgame.org/play
echo 'Testing admin login...'
curl -I https://muh5.ccgame.org/admin/login
echo 'Testing api sdk bootstrap...'
curl -s https://muh5.ccgame.org/api/sdk/bootstrap | head -c 300
echo ''

echo 'Cleaning up temp directory...'
rm -rf /tmp/ccgame-muh5-sync

echo 'Deployment finished successfully.'
"@

ssh ccgame-prod $RemoteCmd
