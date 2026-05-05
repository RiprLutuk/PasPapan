#!/bin/bash
# ============================================================
# PasPapan - Auto Update Script
# Usage: PASPAPAN_UPDATE_CONFIRM=main bash update.sh
# ============================================================

set -e

TARGET_BRANCH="${PASPAPAN_RELEASE_BRANCH:-main}"
MAINTENANCE_ENABLED=0

cleanup() {
    if [ "$MAINTENANCE_ENABLED" = "1" ]; then
        echo ""
        echo "🟢 Leaving maintenance mode..."
        php artisan up --quiet || true
    fi
}
trap cleanup EXIT

echo ""
echo "🔄 PasPapan Auto Updater"
echo "========================"
echo ""

if [ "$TARGET_BRANCH" != "main" ]; then
    echo "❌ Refusing to update from '$TARGET_BRANCH'. Production releases must use main."
    echo "   Set PASPAPAN_RELEASE_BRANCH=main and rerun."
    exit 1
fi

# 1. Fetch and summarize before the destructive reset
echo "📥 Fetching latest code..."
git fetch origin

CURRENT_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
CURRENT_HEAD="$(git rev-parse --short HEAD)"
TARGET_HEAD="$(git rev-parse --short "origin/${TARGET_BRANCH}")"

echo ""
echo "🔎 Preflight summary"
echo "   Working tree: $(pwd)"
echo "   Current branch: ${CURRENT_BRANCH}"
echo "   Current HEAD: ${CURRENT_HEAD}"
echo "   Target ref: origin/${TARGET_BRANCH} (${TARGET_HEAD})"
echo "   Maintenance mode: ${PASPAPAN_UPDATE_MAINTENANCE_MODE:-0}"
echo "   View cache: ${PASPAPAN_UPDATE_VIEW_CACHE:-0}"
echo "   Discard local changes: ${PASPAPAN_UPDATE_DISCARD_LOCAL_CHANGES:-0}"

if ! git diff --quiet || ! git diff --cached --quiet; then
    if [ "${PASPAPAN_UPDATE_DISCARD_LOCAL_CHANGES:-}" != "1" ]; then
        echo "❌ Local tracked changes detected. Refusing to discard them without explicit approval."
        echo "   Rerun with PASPAPAN_UPDATE_DISCARD_LOCAL_CHANGES=1 after backing up anything important."
        exit 1
    fi
fi

if [ "${PASPAPAN_UPDATE_CONFIRM:-}" != "$TARGET_BRANCH" ] && [ "${1:-}" != "--yes" ]; then
    echo ""
    echo "⚠️  This script will run: git reset --hard origin/${TARGET_BRANCH}"
    echo "   Confirm intentionally with:"
    echo "   PASPAPAN_UPDATE_CONFIRM=${TARGET_BRANCH} bash update.sh"
    exit 1
fi

if [ "${PASPAPAN_UPDATE_MAINTENANCE_MODE:-}" = "1" ]; then
    echo ""
    echo "🔴 Entering maintenance mode..."
    php artisan down --retry=60 --quiet || true
    MAINTENANCE_ENABLED=1
fi

echo ""
echo "📥 Resetting to origin/${TARGET_BRANCH}..."
git reset --hard "origin/${TARGET_BRANCH}"
echo "   ✅ Code updated"

# 2. Install PHP dependencies
echo ""
echo "📦 Installing PHP dependencies..."
composer install --optimize-autoloader --no-dev --quiet
echo "   ✅ Composer done"

# 3. Install JS dependencies & build
echo ""
echo "📦 Installing JS dependencies & building assets..."
bun install --silent 2>/dev/null
bun run build
echo "   ✅ Frontend built"

# 4. Clear stale cache before migrations
echo ""
echo "🧹 Clearing stale application cache..."
php artisan optimize:clear --quiet
echo "   ✅ Cache cleared"

# 5. Run migrations
echo ""
echo "🗃️  Running database migrations..."
php artisan migrate --force
echo "   ✅ Migrations done"

# 6. Clear & rebuild cache
echo ""
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan event:cache
php artisan route:cache
if [ "${PASPAPAN_UPDATE_VIEW_CACHE:-}" = "1" ]; then
    php artisan view:cache
else
    echo "   ℹ️  Skipping view cache. Set PASPAPAN_UPDATE_VIEW_CACHE=1 to enable it."
fi
echo "   ✅ Cache optimized"

# 7. Restart queue workers (if running)
if command -v supervisorctl &> /dev/null; then
    echo ""
    echo "🔁 Restarting queue workers..."
    supervisorctl restart all 2>/dev/null || true
    echo "   ✅ Workers restarted"
fi

echo ""
echo "============================================"
echo "🎉 Update complete! PasPapan is up to date."
echo "============================================"
echo ""
