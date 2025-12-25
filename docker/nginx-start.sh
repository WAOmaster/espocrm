#!/bin/bash
# Wait for PHP-FPM to be ready before starting nginx
# This prevents "connection refused" errors on startup

MAX_WAIT=30
WAIT_COUNT=0

echo "Waiting for PHP-FPM to be ready on 127.0.0.1:9000..."

while ! nc -z 127.0.0.1 9000; do
    WAIT_COUNT=$((WAIT_COUNT + 1))
    if [ $WAIT_COUNT -ge $MAX_WAIT ]; then
        echo "ERROR: PHP-FPM did not become ready within ${MAX_WAIT} seconds"
        exit 1
    fi
    echo "Waiting for PHP-FPM... ($WAIT_COUNT/${MAX_WAIT})"
    sleep 1
done

echo "PHP-FPM is ready, starting nginx..."
exec /usr/sbin/nginx -g 'daemon off;'
