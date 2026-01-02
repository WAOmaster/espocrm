FROM php:8.3-fpm

# Install system dependencies (without Node.js - we'll install it separately for correct version)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libldap2-dev \
    libssl-dev \
    libpq-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    cron \
    ca-certificates \
    gnupg \
    netcat-openbsd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Node.js 20 LTS (required by EspoCRM - package.json specifies node >=20)
RUN mkdir -p /etc/apt/keyrings \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_20.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list \
    && apt-get update \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && node --version && npm --version

# Install PHP extensions (includes both MySQL and PostgreSQL support)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    xml \
    soap \
    ldap \
    iconv \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install npm dependencies and build frontend assets
# The build creates: client/lib/espo.js, client/lib/espo-*.js, client/css/espo/*.css
RUN echo "Starting frontend build..." \
    && npm install --legacy-peer-deps \
    && echo "Running grunt internal (build-frontend)..." \
    && npm run build-frontend \
    && echo "Verifying build output..." \
    && ls -la client/lib/ \
    && ls -la client/css/espo/ \
    && test -f client/lib/espo.js || (echo "ERROR: client/lib/espo.js not found!" && exit 1) \
    && test -f client/css/espo/espo.css || (echo "ERROR: client/css/espo/espo.css not found!" && exit 1) \
    && echo "Frontend build completed successfully!" \
    && rm -rf node_modules

# Copy nginx configuration
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/sites-available/default

# Copy PHP-FPM configuration (optimized for Cloud Run)
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Copy supervisor configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy entrypoint script
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Copy nginx startup script (waits for PHP-FPM)
COPY docker/nginx-start.sh /usr/local/bin/nginx-start.sh
RUN chmod +x /usr/local/bin/nginx-start.sh

# Create pre-configured config.php for MySQL database
RUN cat > /var/www/html/data/config.php <<'EOF'
<?php
return [
    'database' => [
        'host' => '34.22.223.99',
        'port' => '3306',
        'charset' => 'utf8mb4',
        'dbname' => 'espocrm_fresh',
        'user' => 'root',
        'password' => 'EspoCRM2025',
        'driver' => 'pdo_mysql',
        'platform' => 'Mysql',
    ],
    'siteUrl' => 'https://espocrm-1050025521391.europe-west1.run.app',
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

# Set permissions - create directories first, then set permissions only on existing files
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/data /var/www/html/custom /var/www/html/client/custom \
    && chmod -R 775 /var/www/html/data \
    && chmod -R 775 /var/www/html/custom \
    && chmod -R 775 /var/www/html/client/custom \
    && chmod -R 755 /var/www/html/client/lib \
    && chmod -R 755 /var/www/html/client/css \
    && chmod -R 755 /var/www/html/client/img \
    && find /var/www/html -name ".htaccess" -type f -exec chmod 644 {} \; \
    && chmod 644 /var/www/html/data/config.php \
    && chown www-data:www-data /var/www/html/data/config.php

# Configure PHP (memory_limit set in php-fpm.conf for per-worker control)
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit=256M" >> "$PHP_INI_DIR/php.ini" \
    && echo "max_execution_time=180" >> "$PHP_INI_DIR/php.ini" \
    && echo "max_input_time=180" >> "$PHP_INI_DIR/php.ini" \
    && echo "post_max_size=50M" >> "$PHP_INI_DIR/php.ini" \
    && echo "upload_max_filesize=50M" >> "$PHP_INI_DIR/php.ini"

# Expose port (Cloud Run uses PORT env variable)
ENV PORT=8080
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:8080/ || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
