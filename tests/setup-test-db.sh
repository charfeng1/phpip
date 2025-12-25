#!/bin/bash
#
# Setup the test database for phpIP
#
# This script creates the test database and loads the schema.
# Run this once before running tests, or whenever you need a fresh test DB.
#
# Usage: ./tests/setup-test-db.sh
#

set -e

# Configuration - matches .env.testing
DB_NAME="phpip_test"
DB_USER="phpip"
DB_PASS="phpip"
DB_HOST="127.0.0.1"
DB_PORT="5432"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
SCHEMA_FILE="$PROJECT_DIR/database/schema/postgres-schema.sql"

export PGPASSWORD="$DB_PASS"

echo "=== phpIP Test Database Setup ==="
echo ""

# Check if schema file exists
if [ ! -f "$SCHEMA_FILE" ]; then
    echo "ERROR: Schema file not found: $SCHEMA_FILE"
    exit 1
fi

echo "1. Dropping existing test database (if exists)..."
dropdb --if-exists -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" "$DB_NAME" 2>/dev/null || true

echo "2. Creating test database..."
createdb -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" "$DB_NAME"

echo "3. Loading schema from postgres-schema.sql..."
psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -f "$SCHEMA_FILE" -q

echo "4. Generating APP_KEY for testing (if not set)..."
cd "$PROJECT_DIR"
if ! grep -q "^APP_KEY=base64:" .env.testing 2>/dev/null; then
    php artisan key:generate --env=testing --force
fi

echo "5. Running migrations..."
php artisan migrate --env=testing --force

echo "6. Seeding basic reference data..."
# Seed just enough data for tests to work
php artisan db:seed --class=CountryTableSeeder --env=testing --force 2>/dev/null || echo "   (CountryTableSeeder failed, continuing)"
php artisan db:seed --class=MatterCategoryTableSeeder --env=testing --force 2>/dev/null || echo "   (MatterCategoryTableSeeder failed, continuing)"
php artisan db:seed --class=ActorRoleTableSeeder --env=testing --force 2>/dev/null || echo "   (ActorRoleTableSeeder failed, continuing)"
php artisan db:seed --class=EventNameTableSeeder --env=testing --force 2>/dev/null || echo "   (EventNameTableSeeder failed, continuing)"
php artisan db:seed --class=ActorTableSeeder --env=testing --force 2>/dev/null || echo "   (ActorTableSeeder failed, continuing)"

echo ""
echo "=== Test database setup complete ==="
echo ""
echo "You can now run tests with: php artisan test"
echo ""
