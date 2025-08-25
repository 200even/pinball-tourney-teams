#!/bin/bash

echo "=== Starting Pinball Tournament Tracker ==="

# Environment info
echo "Environment variables:"
echo "APP_ENV: $APP_ENV"
echo "APP_DEBUG: $APP_DEBUG" 
echo "APP_KEY: ${APP_KEY:0:10}..." # Only show first 10 chars for security
echo "DB_CONNECTION: $DB_CONNECTION"
echo "DB_HOST: $DB_HOST"
echo "DB_DATABASE: $DB_DATABASE"
echo "DATABASE_URL: ${DATABASE_URL:0:20}..." # Show first 20 chars
echo "PORT: ${PORT:-8080}"

# Generate app key if missing
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY missing - generating one..."
    php artisan key:generate --force || echo "Key generation failed"
else
    echo "APP_KEY is set"
fi

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

# DON'T cache routes in production - causes issues with route discovery
echo "Route caching disabled - using route discovery"

echo "Testing routes..."
php artisan route:list | head -5 || echo "Route list failed"

echo "Starting Laravel server..."
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8080} --verbose
