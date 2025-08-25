#!/bin/bash

echo "Starting Pinball Tournament Tracker..."

# Check if database connection works
echo "Testing database connection..."
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connected successfully';" || echo "Database connection failed"

# Run migrations if database is available
echo "Running migrations..."
php artisan migrate --force || echo "Migrations failed, continuing anyway..."

echo "Starting Laravel server..."
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
