#!/bin/bash
set -e

# Run Migrations
echo "Running migrations..."
php artisan migrate --force

# Seed Data (Optional - usually only run once manually, but for this setup we can be aggressive or check if seeded)
# For now, let's run it. Idempotent seeders are best.
echo "Seeding database..."
php artisan db:seed --force

# Generate Swagger
echo "Generating documentation..."
php artisan l5-swagger:generate

# Start Apache
echo "Starting Apache..."
exec apache2-foreground
