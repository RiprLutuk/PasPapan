#!/bin/bash
# ============================================================
# PasPapan - Auto Update Script
# Usage: bash update.sh
# ============================================================

set -e

echo ""
echo "🔄 PasPapan Auto Updater"
echo "========================"
echo ""

# 1. Pull latest from main (force reset)
echo "📥 Pulling latest code..."
git fetch origin
git reset --hard origin/main
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

# 4. Run migrations
echo ""
echo "🗃️  Running database migrations..."
php artisan migrate --force
echo "   ✅ Migrations done"

# 5. Clear & rebuild cache
echo ""
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache
echo "   ✅ Cache optimized"

# 6. Restart queue workers (if running)
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
