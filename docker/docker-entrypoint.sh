#!/bin/bash
set -e

echo "Starting EspoCRM initialization..."

# Database configuration from environment variables with defaults
DB_HOST="${DATABASE_HOST:-34.22.223.99}"
DB_PORT="${DATABASE_PORT:-3306}"
DB_NAME="${DATABASE_NAME:-espocrm_fresh}"
DB_USER="${DATABASE_USER:-root}"
DB_PASSWORD="${DATABASE_PASSWORD:-EspoCRM2025}"
SITE_URL="${SITE_URL:-https://espocrm-1050025521391.europe-west1.run.app}"

echo "Database config: host=$DB_HOST, dbname=$DB_NAME, user=$DB_USER"

# Create required directories if they don't exist
mkdir -p /var/www/html/public
mkdir -p /var/www/html/data
mkdir -p /var/www/html/data/cache
mkdir -p /var/www/html/data/logs
mkdir -p /var/www/html/data/upload
mkdir -p /var/www/html/data/tmp
mkdir -p /var/www/html/custom
mkdir -p /var/www/html/client/custom

# Ensure public directory exists and has proper structure
echo "Verifying public directory structure..."

# Public directory should already exist from the COPY in Dockerfile
# Just ensure permissions are correct
if [ -d "/var/www/html/public" ]; then
    echo "Public directory found - structure is ready"
else
    echo "Warning: Public directory not found!"
fi

# Verify frontend assets are present (built during Docker image build)
echo "Verifying frontend assets..."
if [ -f "/var/www/html/client/lib/espo.js" ]; then
    echo "✓ client/lib/espo.js found"
else
    echo "✗ WARNING: client/lib/espo.js NOT found - frontend may not work!"
fi

if [ -f "/var/www/html/client/css/espo/espo.css" ]; then
    echo "✓ client/css/espo/espo.css found"
else
    echo "✗ WARNING: client/css/espo/espo.css NOT found - frontend may not work!"
fi

if [ -f "/var/www/html/client/img/favicon.ico" ]; then
    echo "✓ client/img/favicon.ico found"
else
    echo "✗ WARNING: client/img/favicon.ico NOT found!"
fi

if [ -f "/var/www/html/client/img/logo-light.svg" ]; then
    echo "✓ client/img/logo-light.svg found"
else
    echo "✗ WARNING: client/img/logo-light.svg NOT found!"
fi

echo "Frontend asset verification complete."

# Set proper permissions
chown -R www-data:www-data /var/www/html/data
chown -R www-data:www-data /var/www/html/custom
chown -R www-data:www-data /var/www/html/client/custom
chown -R www-data:www-data /var/www/html/public
chown -R www-data:www-data /var/www/html/install
chmod -R 775 /var/www/html/data
chmod -R 775 /var/www/html/custom
chmod -R 775 /var/www/html/client/custom
chmod -R 755 /var/www/html/public
chmod -R 755 /var/www/html/install

# Ensure client directory and its contents are readable by nginx
chmod -R 755 /var/www/html/client
chown -R www-data:www-data /var/www/html/client

# Create config.php if it doesn't exist
if [ ! -f "/var/www/html/data/config.php" ]; then
    echo "Config.php not found. Checking installation status..."

    # Check if database is already installed by looking for user table
    DB_INSTALLED=$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name='user';" 2>/dev/null || echo "0")

    if [ "$DB_INSTALLED" = "1" ]; then
        echo "Database is already installed. Creating full config.php..."
        cat > /var/www/html/data/config.php <<EOF
<?php
return [
    'database' => [
        'host' => '$DB_HOST',
        'port' => '$DB_PORT',
        'charset' => 'utf8mb4',
        'dbname' => '$DB_NAME',
        'user' => '$DB_USER',
        'password' => '$DB_PASSWORD',
        'driver' => 'pdo_mysql',
        'platform' => 'Mysql',
    ],
    'isInstalled' => true,
    'siteUrl' => '$SITE_URL',
    'useCache' => true,
    'recordsPerPage' => 20,
    'recordsPerPageSmall' => 5,
    'applicationName' => 'EspoCRM',
    'version' => '9.2.5',
    'timeZone' => 'Asia/Colombo',
    'dateFormat' => 'DD/MM/YYYY',
    'timeFormat' => 'HH:mm',
    'weekStart' => 1,
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
    else
        echo "Database not installed. Creating minimal config.php for installation wizard..."
        cat > /var/www/html/data/config.php <<EOF
<?php
return [
    'database' => [
        'host' => '$DB_HOST',
        'port' => '$DB_PORT',
        'charset' => 'utf8mb4',
        'dbname' => '$DB_NAME',
        'user' => '$DB_USER',
        'password' => '$DB_PASSWORD',
        'driver' => 'pdo_mysql',
        'platform' => 'Mysql',
    ],
];
EOF
    fi
    chown www-data:www-data /var/www/html/data/config.php
    chmod 644 /var/www/html/data/config.php
    echo "Config.php created successfully."
fi

# Clear cache on startup
if [ -f "/var/www/html/data/config.php" ]; then
    echo "Clearing cache..."
    rm -rf /var/www/html/data/cache/*
    echo "Cache cleared."

    echo "Running rebuild..."
    su -s /bin/bash www-data -c "php rebuild.php"
    echo "Rebuild complete."
fi

# Create log directory for nginx and supervisor
mkdir -p /var/log/nginx
mkdir -p /var/log/supervisor
chown -R www-data:www-data /var/log/nginx

echo "EspoCRM initialization complete."

# Execute the CMD
exec "$@"
