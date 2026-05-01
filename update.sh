#!/bin/bash
# ============================================================
# PasPapan - Auto Update Script
# Usage: PASPAPAN_UPDATE_CONFIRM=main bash update.sh
# ============================================================

set -e

TARGET_BRANCH="${PASPAPAN_RELEASE_BRANCH:-main}"

echo ""
echo "🔄 PasPapan Auto Updater"
echo "========================"
echo ""

if [ "$TARGET_BRANCH" != "main" ]; then
    echo "❌ Refusing to update from '$TARGET_BRANCH'. Production releases must use main."
    echo "   Set PASPAPAN_RELEASE_BRANCH=main and rerun."
    exit 1
fi

if [ "${PASPAPAN_UPDATE_CONFIRM:-}" != "$TARGET_BRANCH" ] && [ "${1:-}" != "--yes" ]; then
    echo "⚠️  This script performs a destructive git reset to origin/${TARGET_BRANCH}."
    echo "   Confirm intentionally with:"
    echo "   PASPAPAN_UPDATE_CONFIRM=${TARGET_BRANCH} bash update.sh"
    exit 1
fi

# 1. Pull latest from main (force reset)
echo "📥 Pulling latest code..."
git fetch origin

if ! git diff --quiet || ! git diff --cached --quiet; then
    if [ "${PASPAPAN_UPDATE_DISCARD_LOCAL_CHANGES:-}" != "1" ]; then
        echo "❌ Local tracked changes detected. Refusing to discard them without explicit approval."
        echo "   Rerun with PASPAPAN_UPDATE_DISCARD_LOCAL_CHANGES=1 after backing up anything important."
        exit 1
    fi
fi

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
php artisan view:cache
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
