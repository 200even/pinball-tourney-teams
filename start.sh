#!/bin/bash

echo "=== Starting Pinball Tournament Tracker ==="

# Environment info
echo "Environment variables:"
echo "APP_ENV: $APP_ENV"
echo "APP_DEBUG: $APP_DEBUG"

# Temporarily enable debug mode for troubleshooting
export APP_DEBUG=true
echo "Debug mode enabled for troubleshooting" 
echo "APP_KEY: ${APP_KEY:0:10}..." # Only show first 10 chars for security
echo "DB_CONNECTION: $DB_CONNECTION"
echo "DB_HOST: $DB_HOST"
echo "DB_PORT: $DB_PORT"
echo "DB_DATABASE: $DB_DATABASE"
echo "DB_USERNAME: $DB_USERNAME"
echo "DATABASE_URL: ${DATABASE_URL:0:30}..." # Show first 30 chars

# Check if Railway database variables are set
if [ -n "$DATABASE_URL" ]; then
    echo "DATABASE_URL is available - parsing..."
    # Extract components from DATABASE_URL for debugging
    echo "Parsed DATABASE_URL components:"
    echo "$DATABASE_URL" | sed 's/.*:\/\/\([^:]*\):\([^@]*\)@\([^:]*\):\([^\/]*\)\/\(.*\)/Host: \3\nPort: \4\nDatabase: \5\nUser: \1/'
else
    echo "DATABASE_URL not set - using individual DB_ variables"
fi
echo "PORT: ${PORT:-8080}"

# Generate app key if missing
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY missing - generating one..."
    php artisan key:generate --force || echo "Key generation failed"
else
    echo "APP_KEY is set"
fi

# Check if Laravel can start (don't cache config in production)
echo "Testing Laravel configuration..."
echo "Skipping config cache to allow dynamic environment variables"

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

# Database is now working - can use database sessions
export SESSION_DRIVER=database
echo "Using database sessions (database connection working)"

# DON'T cache routes in production - causes issues with route discovery
echo "Route caching disabled - using route discovery"

echo "Testing routes..."
php artisan route:list | head -10 || echo "Route list failed"
echo "Checking specific routes..."
php artisan route:list | grep -E "(test|debug|up)" || echo "Test routes not found"

echo "Testing basic Laravel bootstrap..."
php -r "
try {
    require_once 'vendor/autoload.php';
    \$app = require_once 'bootstrap/app.php';
    echo 'Laravel bootstrap: SUCCESS' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Laravel bootstrap: FAILED - ' . \$e->getMessage() . PHP_EOL;
    echo 'Stack trace: ' . \$e->getTraceAsString() . PHP_EOL;
}
"

echo "Starting Laravel server..."
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8080} --verbose
