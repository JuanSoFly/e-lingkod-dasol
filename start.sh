#!/bin/bash
set -e

echo "🚀 Starting E-Lingkod Dasol HRIS..."

# Create required temp directories
mkdir -p /tmp/nginx-client-body /tmp/nginx-proxy /tmp/nginx-fastcgi /tmp/nginx-uwsgi /tmp/nginx-scgi

# Ensure Laravel storage directories exist
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/public bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Create storage symlink if it doesn't exist
if [ ! -L public/storage ]; then
    echo "🔗 Creating storage symlink..."
    php artisan storage:link --force 2>/dev/null || true
fi

# Run migrations if database is available (non-blocking)
echo "🗄️ Running database migrations..."
php artisan migrate --force 2>/dev/null || echo "⚠️ Migrations skipped (database may not be ready yet)"

# Substitute Render's dynamic PORT into nginx config
export PORT="${PORT:-10000}"
envsubst '${PORT}' < /app/nginx.conf > /tmp/nginx.conf

# Generate Nginx mime.types and fastcgi_params dynamically to ensure they exist
cat << 'EOF' > /tmp/mime.types
types {
    text/html                             html htm shtml;
    text/css                              css;
    text/xml                              xml;
    image/gif                             gif;
    image/jpeg                            jpeg jpg;
    application/javascript                js;
    image/png                             png;
    image/svg+xml                         svg svgz;
    application/font-woff                 woff;
    application/font-woff2                woff2;
    application/json                      json;
    application/pdf                       pdf;
    application/zip                       zip;
}
EOF

cat << 'EOF' > /tmp/fastcgi_params
fastcgi_param  QUERY_STRING       $query_string;
fastcgi_param  REQUEST_METHOD     $request_method;
fastcgi_param  CONTENT_TYPE       $content_type;
fastcgi_param  CONTENT_LENGTH     $content_length;
fastcgi_param  SCRIPT_NAME        $fastcgi_script_name;
fastcgi_param  REQUEST_URI        $request_uri;
fastcgi_param  DOCUMENT_URI       $document_uri;
fastcgi_param  DOCUMENT_ROOT      $document_root;
fastcgi_param  SERVER_PROTOCOL    $server_protocol;
fastcgi_param  REQUEST_SCHEME     $scheme;
fastcgi_param  HTTPS              $https if_not_empty;
fastcgi_param  GATEWAY_INTERFACE  CGI/1.1;
fastcgi_param  SERVER_SOFTWARE    nginx/$nginx_version;
fastcgi_param  REMOTE_ADDR        $remote_addr;
fastcgi_param  REMOTE_PORT        $remote_port;
fastcgi_param  SERVER_ADDR        $server_addr;
fastcgi_param  SERVER_PORT        $server_port;
fastcgi_param  SERVER_NAME        $server_name;
EOF

# Cache Laravel config, routes, and views for production
echo "📦 Caching Laravel configuration..."
cd /app
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "✅ Starting services via supervisord..."
exec supervisord -c /app/supervisord.conf
