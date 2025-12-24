#!/bin/bash
set -e

echo "Starting EspoCRM initialization..."

# Create required directories if they don't exist
mkdir -p /var/www/html/data
mkdir -p /var/www/html/data/cache
mkdir -p /var/www/html/data/logs
mkdir -p /var/www/html/data/upload
mkdir -p /var/www/html/data/tmp
mkdir -p /var/www/html/custom
mkdir -p /var/www/html/client/custom

# Set proper permissions
chown -R www-data:www-data /var/www/html/data
chown -R www-data:www-data /var/www/html/custom
chown -R www-data:www-data /var/www/html/client/custom
chmod -R 775 /var/www/html/data
chmod -R 775 /var/www/html/custom
chmod -R 775 /var/www/html/client/custom

# If config.php doesn't exist and we have environment variables, create it
if [ ! -f "/var/www/html/data/config.php" ] && [ -n "$DATABASE_HOST" ]; then
    echo "Creating initial config.php from environment variables..."
    cat > /var/www/html/data/config.php <<EOF
<?php
return [
    'database' => [
        'host' => '${DATABASE_HOST:-localhost}',
        'port' => '${DATABASE_PORT:-3306}',
        'charset' => 'utf8mb4',
        'dbname' => '${DATABASE_NAME:-espocrm}',
        'user' => '${DATABASE_USER:-root}',
        'password' => '${DATABASE_PASSWORD:-}',
        'driver' => 'pdo_mysql',
    ],
    'siteUrl' => '${SITE_URL:-https://example.com}',
    'useCache' => true,
    'recordsPerPage' => 20,
    'recordsPerPageSmall' => 5,
    'applicationName' => 'EspoCRM',
    'version' => '9.2.5',
    'timeZone' => 'UTC',
    'dateFormat' => 'MM/DD/YYYY',
    'timeFormat' => 'HH:mm',
    'weekStart' => 0,
    'thousandSeparator' => ',',
    'decimalMark' => '.',
    'exportDelimiter' => ',',
    'currency' => 'LKR',
    'baseCurrency' => 'LKR',
    'defaultCurrency' => 'LKR',
    'currencyRates' => [],
    'currencyNoJoinMode' => false,
    'outboundEmailIsShared' => true,
    'outboundEmailFromName' => 'EspoCRM',
    'outboundEmailFromAddress' => 'crm@example.com',
    'smtpServer' => '',
    'smtpPort' => 587,
    'smtpAuth' => true,
    'smtpSecurity' => 'TLS',
    'smtpUsername' => '',
    'smtpPassword' => '',
    'language' => 'en_US',
    'logger' => [
        'path' => 'data/logs/espo.log',
        'level' => 'WARNING',
        'rotation' => true,
        'maxFileNumber' => 30,
    ],
    'authenticationMethod' => 'Espo',
    'globalSearchMaxSize' => 10,
    'passwordRecoveryDisabled' => false,
    'passwordRecoveryForAdminDisabled' => false,
    'passwordRecoveryForInternalUsersDisabled' => false,
    'passwordRecoveryNoExposure' => false,
    'emailKeepParentTeamsEntityList' => ['Case'],
    'streamEmailWithContentEntityTypeList' => ['Case'],
    'recordListMaxSizeLimit' => 200,
    'noteDeleteThresholdPeriod' => '1 month',
    'noteEditThresholdPeriod' => '7 days',
    'cleanupDeletedRecords' => true,
];
EOF
    chown www-data:www-data /var/www/html/data/config.php
    chmod 644 /var/www/html/data/config.php
    echo "Config.php created successfully."
fi

# Clear cache on startup
if [ -f "/var/www/html/data/config.php" ]; then
    echo "Clearing cache..."
    rm -rf /var/www/html/data/cache/*
    echo "Cache cleared."
fi

# Create log directory for nginx and supervisor
mkdir -p /var/log/nginx
mkdir -p /var/log/supervisor
chown -R www-data:www-data /var/log/nginx

echo "EspoCRM initialization complete."

# Execute the CMD
exec "$@"
