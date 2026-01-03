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
    default-mysql-client \
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

# Don't create config.php here - let entrypoint script handle it dynamically
# This allows the container to detect if installation is already complete

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
    && echo "max_execution_time=600" >> "$PHP_INI_DIR/php.ini" \
    && echo "max_input_time=600" >> "$PHP_INI_DIR/php.ini" \
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
