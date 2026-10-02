#!/bin/sh
set -e

# 1. Cấu hình cổng lắng nghe linh hoạt từ biến môi trường của Cloud (mặc định 10000 trên Render hoặc 80)
PORT=${PORT:-10000}
echo "==> Configuring Nginx to listen on port: $PORT"
sed -i "s/LISTEN_PORT/${PORT}/g" /etc/nginx/http.d/default.conf

# 2. Khởi tạo cấu trúc thư mục lưu trữ và phân quyền ghi
echo "==> Ensuring required storage directories and permissions..."
mkdir -p /var/www/html/storage/app/temp
mkdir -p /var/www/html/storage/app/lesson_plan_templates
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/bootstrap/cache

chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# 3. Tạo symbolic link cho Storage
echo "==> Creating storage link..."
php artisan storage:link --force || true

# 4. Tự động kiểm tra và chạy Database Migration nếu có cấu hình DB
if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "127.0.0.1" ]; then
    echo "==> Detected Cloud Database ($DB_HOST). Running migrations..."
    php artisan migrate --force || echo "==> Warning: Database migration skipped or failed. Continuing startup..."
else
    echo "==> No external DB_HOST provided or set to localhost. Skipping auto-migration."
fi

# 5. Tối ưu bộ nhớ đệm cho Laravel trong môi trường Production
if [ "$APP_ENV" = "production" ] || [ "$APP_ENV" = "prod" ]; then
    echo "==> Production environment detected. Optimizing caches..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "==> Starting web services (Nginx & PHP-FPM) via Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
