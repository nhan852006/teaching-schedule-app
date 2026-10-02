#!/bin/sh
set -e

# 1. Cấu hình cổng lắng nghe linh hoạt từ biến môi trường của Cloud (mặc định 10000 trên Render hoặc 80)
PORT=${PORT:-10000}
echo "==> Configuring Nginx to listen on port: $PORT"
sed -i "s/LISTEN_PORT/${PORT}/g" /etc/nginx/http.d/default.conf

# 2. Khởi tạo cấu trúc thư mục lưu trữ và phân quyền ghi
echo "==> Ensuring required storage directories..."
mkdir -p /var/www/html/storage/app/temp
mkdir -p /var/www/html/storage/app/lesson_plan_templates
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/bootstrap/cache

# 3. Thiết lập thông số môi trường chuẩn kết nối TiDB Cloud & bảo mật Laravel
export APP_NAME="${APP_NAME:-Hệ Thống Lịch Giảng Dạy}"
export APP_ENV="${APP_ENV:-production}"
export APP_KEY="${APP_KEY:-base64:qStF1AGo/5lPD4aFW1WJ3fLYgx5EnkxL+spw43vXxzM=}"
export APP_DEBUG="${APP_DEBUG:-true}"
export APP_URL="${APP_URL:-https://teaching-schedule-app-xgcv.onrender.com}"

export DB_CONNECTION="${DB_CONNECTION:-mysql}"
export DB_HOST="${DB_HOST:-gateway01.ap-southeast-1.prod.aws.tidbcloud.com}"
export DB_PORT="${DB_PORT:-4000}"
export DB_DATABASE="${DB_DATABASE:-teaching_schedule}"
export DB_USERNAME="${DB_USERNAME:-iAz3Sb8PKvwUN1D.root}"
export DB_PASSWORD="${DB_PASSWORD:-Tl5nqVfJF5UpSw3K}"
export MYSQL_ATTR_SSL_CA="${MYSQL_ATTR_SSL_CA:-/etc/ssl/certs/ca-certificates.crt}"
export MYSQL_ATTR_SSL_VERIFY_SERVER_CERT="${MYSQL_ATTR_SSL_VERIFY_SERVER_CERT:-false}"

export SESSION_DRIVER="${SESSION_DRIVER:-file}"
export SESSION_LIFETIME="${SESSION_LIFETIME:-120}"
export CACHE_STORE="${CACHE_STORE:-file}"
export QUEUE_CONNECTION="${QUEUE_CONNECTION:-sync}"

echo "==> Writing runtime configuration into .env..."
cat <<EOF > /var/www/html/.env
APP_NAME="${APP_NAME}"
APP_ENV=${APP_ENV}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG}
APP_URL=${APP_URL}

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=${DB_CONNECTION}
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
MYSQL_ATTR_SSL_CA=${MYSQL_ATTR_SSL_CA}
MYSQL_ATTR_SSL_VERIFY_SERVER_CERT=${MYSQL_ATTR_SSL_VERIFY_SERVER_CERT}

SESSION_DRIVER=${SESSION_DRIVER}
SESSION_LIFETIME=${SESSION_LIFETIME}
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

FILESYSTEM_DISK=local
CACHE_STORE=${CACHE_STORE}
QUEUE_CONNECTION=${QUEUE_CONNECTION}
EOF

# 4. Tạo symbolic link cho Storage
echo "==> Creating storage link..."
php artisan storage:link --force || true

# 5. Tự động kiểm tra và chạy Database Migration
echo "==> Running database migrations on TiDB Cloud ($DB_HOST)..."
php artisan migrate --force || echo "==> Warning: Database migration skipped or already up to date."

# 6. Xoá cache cấu hình cũ
echo "==> Clearing stale config & view caches..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# 7. Đảm bảo phân quyền chính xác cho www-data
echo "==> Fixing permissions for www-data..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/.env
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
chmod 644 /var/www/html/.env

echo "==> Starting web services (Nginx & PHP-FPM) via Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
