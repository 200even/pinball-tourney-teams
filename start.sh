#!/bin/bash

echo "=== Starting Pinball Tournament Tracker ==="

# Environment info
echo "Environment variables:"
echo "APP_ENV: $APP_ENV"
echo "APP_DEBUG: $APP_DEBUG" 
echo "DB_CONNECTION: $DB_CONNECTION"
echo "PORT: ${PORT:-8080}"

# Check if Laravel can start
echo "Testing Laravel configuration..."
php artisan config:cache || echo "Config cache failed"

# Check environment
echo "Testing environment..."
php artisan env || echo "Environment check failed"

# Check if database connection works
echo "Testing database connection..."
php artisan tinker --execute="
try {
    \$pdo = DB::connection()->getPdo();
    echo 'Database connected successfully - Driver: ' . \$pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . PHP_EOL;
} catch (Exception \$e) {
    echo 'Database connection failed: ' . \$e->getMessage() . PHP_EOL;
}
" || echo "Database test completely failed"

# Run migrations if database is available
echo "Running migrations..."
php artisan migrate --force --verbose || echo "Migrations failed, continuing anyway..."

# Clear any cached configs that might be problematic
echo "Clearing caches..."
php artisan config:clear || echo "Config clear failed"
php artisan route:clear || echo "Route clear failed"
php artisan view:clear || echo "View clear failed"

echo "Testing routes..."
php artisan route:list | head -5 || echo "Route list failed"

echo "Starting Laravel server..."
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8080} --verbose
