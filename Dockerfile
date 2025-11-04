# syntax=docker/dockerfile:1
FROM php:8.3-fpm-alpine

# Install system dependencies including nginx
RUN apk add --no-cache \
    curl \
    git \
    unzip \
    icu-data-full \
    libzip \
    libzip-dev \
    libpng \
    libpng-dev \
    libjpeg-turbo \
    libjpeg-turbo-dev \
    freetype \
    freetype-dev \
    icu-dev \
    nodejs \
    npm \
    nginx \
    supervisor

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    zip \
    bcmath \
    opcache \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

WORKDIR /app

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /app

# Create Laravel storage directories and set permissions
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/public bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache \
    && chown -R www-data:www-data /app

# Create .env file for production
RUN cp .env.example .env

# Install PHP dependencies
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# Generate application key and create storage symlink
RUN php artisan key:generate \
    && php artisan storage:link

# Build frontend assets
RUN npm install && npm run build

# Create nginx configuration
RUN cat > /etc/nginx/nginx.conf << 'EOF'
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;

events {
    worker_connections 1024;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';

    access_log /var/log/nginx/access.log main;

    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml application/atom+xml image/svg+xml;

    server {
        listen 8080;
        server_name _;
        root /var/www/html/public;
        index index.php index.html;

        # Handle static assets directly
        location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
            expires 1y;
            add_header Cache-Control "public, immutable";
            try_files $uri =404;
        }

        # Handle Laravel routes
        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        # PHP-FPM configuration
        location ~ \.php$ {
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_param QUERY_STRING $query_string;
            fastcgi_param REQUEST_METHOD $request_method;
            fastcgi_param CONTENT_TYPE $content_type;
            fastcgi_param CONTENT_LENGTH $content_length;
            fastcgi_param SCRIPT_NAME $fastcgi_script_name;
            fastcgi_param REQUEST_URI $request_uri;
            fastcgi_param DOCUMENT_URI $document_uri;
            fastcgi_param DOCUMENT_ROOT $document_root;
            fastcgi_param SERVER_PROTOCOL $server_protocol;
            fastcgi_param GATEWAY_INTERFACE CGI/1.1;
            fastcgi_param SERVER_SOFTWARE nginx/$nginx_version;
            fastcgi_param REMOTE_ADDR $remote_addr;
            fastcgi_param REMOTE_PORT $remote_port;
            fastcgi_param SERVER_ADDR $server_addr;
            fastcgi_param SERVER_PORT $server_port;
            fastcgi_param SERVER_NAME $server_name;
            fastcgi_param HTTPS $https if_not_empty;
            include fastcgi_params;

            # Increase timeout for long-running scripts
            fastcgi_read_timeout 300;

            # Buffer settings
            fastcgi_buffer_size 128k;
            fastcgi_buffers 256 16k;
            fastcgi_busy_buffers_size 256k;
        }

        location ~ /\.ht {
            deny all;
        }
        # Security headers
        add_header X-Frame-Options "SAMEORIGIN" always;
        add_header X-Content-Type-Options "nosniff" always;
        add_header X-XSS-Protection "1; mode=block" always;
        add_header Referrer-Policy "strict-origin-when-cross-origin" always;
        add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:;" always;

        # Hide Nginx version
        server_tokens off;
    }
}
EOF

# Configure PHP-FPM to use TCP (simpler and more reliable than Unix sockets)
RUN sed -i 's|listen = 127.0.0.1:9000|listen = 127.0.0.1:9000|' /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm = dynamic" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_children = 50" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.start_servers = 5" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.min_spare_servers = 5" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_spare_servers = 35" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "php_admin_value[memory_limit] = 512M" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "php_admin_value[max_execution_time] = 300" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "php_admin_value[upload_max_filesize] = 64M" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "php_admin_value[post_max_size] = 64M" >> /usr/local/etc/php-fpm.d/www.conf

# Create supervisord configuration
RUN mkdir -p /etc/supervisor/conf.d \
    && cat > /etc/supervisor/conf.d/supervisord.conf << 'EOF'
[supervisord]
nodaemon=true
user=root

[program:php-fpm]
command=php-fpm --allow-to-run-as-root
autostart=true
autorestart=true
user=root
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:nginx]
command=nginx -g "daemon off;"
autostart=true
autorestart=true
user=root
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
EOF

# Set environment variables
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV PORT=8000

EXPOSE 8000

# Add health check for Railway monitoring
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD curl -f http://localhost:8000/health || exit 1

# Optimize Laravel for production
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Start command
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
