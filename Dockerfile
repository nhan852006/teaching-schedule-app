# ====================================================
# Giai đoạn 1: Build Frontend Assets (Vite & Tailwind)
# ====================================================
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# ====================================================
# Giai đoạn 2: Production Web Application (PHP 8.2 + Nginx)
# ====================================================
FROM php:8.2-fpm-alpine

# Cài đặt các gói hệ thống và thư viện cần thiết
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    ca-certificates \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev

# Cài đặt & kích hoạt các PHP Extension quan trọng cho Laravel, MySQL và PhpWord
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache \
        xml

# Lấy Composer chính thức
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Sao chép toàn bộ mã nguồn ứng dụng
COPY . /var/www/html

# Sao chép assets đã build từ giai đoạn 1
COPY --from=frontend /app/public/build /var/www/html/public/build

# Cài đặt PHP dependencies chuẩn Production (không cài dev packages để tối ưu dung lượng)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Cấu hình Web Server và Process Manager
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Cấu hình phân quyền thư mục lưu trữ cho Web Server
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Cổng mặc định của Web Service (Render sẽ ghi đè qua biến PORT)
EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
