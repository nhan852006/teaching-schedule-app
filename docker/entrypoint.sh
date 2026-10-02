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

# 3. Đảm bảo file .env tồn tại
if [ ! -f /var/www/html/.env ]; then
    echo "==> Creating default .env from .env.example..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# 4. Đảm bảo APP_KEY luôn có giá trị base64 hợp lệ
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    export APP_KEY="base64:qStF1AGo/5lPD4aFW1WJ3fLYgx5EnkxL+spw43vXxzM="
    echo "==> Exported fallback APP_KEY"
fi

# 5. Tạo symbolic link cho Storage
echo "==> Creating storage link..."
php artisan storage:link --force || true

# 6. Tự động kiểm tra và chạy Database Migration nếu có cấu hình DB
if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "127.0.0.1" ]; then
    echo "==> Detected Cloud Database ($DB_HOST). Running migrations..."
    php artisan migrate --force || echo "==> Warning: Database migration skipped or failed. Continuing startup..."
else
    echo "==> No external DB_HOST provided or set to localhost. Skipping auto-migration."
fi

# 7. Xoá cache cũ để Laravel nạp trực tiếp cấu hình môi trường mới nhất từ Cloud
echo "==> Clearing stale config & view caches..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

echo "==> Starting web services (Nginx & PHP-FPM) via Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
