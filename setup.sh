#!/bin/bash

# EspoCRM Local Development Setup Script
# This script automates the installation of EspoCRM with your MySQL Docker container

set -e

echo "========================================="
echo "EspoCRM Local Development Setup"
echo "========================================="
echo ""

# Load environment variables
if [ -f .env.install ]; then
    source .env.install
    echo "✓ Loaded configuration from .env.install"
else
    echo "✗ Error: .env.install file not found!"
    echo "  Please create .env.install with your database credentials"
    exit 1
fi

# Step 1: Install PHP dependencies
echo ""
echo "Step 1: Installing PHP dependencies..."
if [ ! -d "vendor" ]; then
    composer install
    echo "✓ PHP dependencies installed"
else
    echo "✓ PHP dependencies already installed"
fi

# Step 2: Install Node.js dependencies
echo ""
echo "Step 2: Installing Node.js dependencies..."
if [ ! -d "node_modules" ]; then
    npm install
    echo "✓ Node.js dependencies installed"
else
    echo "✓ Node.js dependencies already installed"
fi

# Step 3: Build frontend
echo ""
echo "Step 3: Building frontend assets..."
npm run build-dev
echo "✓ Frontend built successfully"

# Step 4: Create database
echo ""
echo "Step 4: Creating database..."
MYSQL_CONNECT="mysql -h${DB_HOST} -P${DB_PORT} -u${DB_USER} -p${DB_PASSWORD}"

# Check if database exists
DB_EXISTS=$(echo "SHOW DATABASES LIKE '${DB_NAME}';" | $MYSQL_CONNECT 2>/dev/null | grep -c "${DB_NAME}" || true)

if [ "$DB_EXISTS" -eq 0 ]; then
    echo "CREATE DATABASE ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" | $MYSQL_CONNECT
    echo "✓ Database '${DB_NAME}' created"
else
    echo "✓ Database '${DB_NAME}' already exists"
fi

# Step 5: Run CLI installation
echo ""
echo "Step 5: Running EspoCRM installation..."

# Build host-name parameter (combine host and port)
if [ -n "${DB_PORT}" ] && [ "${DB_PORT}" != "3306" ]; then
    HOST_NAME="${DB_HOST}:${DB_PORT}"
else
    HOST_NAME="${DB_HOST}"
fi

# Step 5a: License agreement
echo "  → Accepting license agreement..."
php install/cli.php -a step1 -d "user-lang=en_US"

# Step 5b: Database configuration
echo "  → Configuring database..."
php install/cli.php -a step2 -d "db-platform=${DB_PLATFORM}&db-name=${DB_NAME}&host-name=${HOST_NAME}&db-user-name=${DB_USER}&db-user-password=${DB_PASSWORD}"

# Step 5c: Setup confirmation
echo "  → Confirming setup..."
php install/cli.php -a setupConfirmation

# Step 5d: Check permissions
echo "  → Checking permissions..."
php install/cli.php -a checkPermission

# Step 5e: Save settings
echo "  → Saving settings..."
php install/cli.php -a saveSettings

# Step 5f: Build system
echo "  → Building system..."
php install/cli.php -a buildSystem

# Step 5g: Create admin user
echo "  → Creating admin user..."
php install/cli.php -a step3 -d "user-name=${ADMIN_USERNAME}&user-pass=${ADMIN_PASSWORD}&user-confirm-pass=${ADMIN_PASSWORD}"

# Step 5h: Create user
echo "  → Setting up admin account..."
php install/cli.php -a createUser

# Step 5i: Finish installation
echo "  → Finalizing installation..."
php install/cli.php -a finish

echo ""
echo "✓ EspoCRM installation completed successfully!"

# Step 6: Set permissions
echo ""
echo "Step 6: Setting file permissions..."
chmod -R 755 data custom client
find . -type d -exec chmod 755 {} + 2>/dev/null || true
find . -type f -exec chmod 644 {} + 2>/dev/null || true
chmod +x bin/* 2>/dev/null || true
echo "✓ Permissions set"

echo ""
echo "========================================="
echo "Installation Complete! 🎉"
echo "========================================="
echo ""
echo "Admin Credentials:"
echo "  Username: ${ADMIN_USERNAME}"
echo "  Password: ${ADMIN_PASSWORD}"
echo ""
echo "To start the development server:"
echo "  php -S localhost:8080 -t public"
echo ""
echo "Then open: http://localhost:8080"
echo ""
echo "Useful commands:"
echo "  Clear cache:    php clear_cache.php"
echo "  Rebuild:        php rebuild.php"
echo "  Run tests:      npm run unit-tests"
echo "  Static analysis: npm run sa"
echo ""
