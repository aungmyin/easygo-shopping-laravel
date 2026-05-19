FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    mariadb-client \
    npm \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . /app

RUN mkdir -p /app/bootstrap/cache /app/storage /app/public/storage

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

RUN npm ci && npm run build

RUN php artisan storage:link

RUN chown -R 1000:1000 /app/storage /app/bootstrap/cache /app/public

EXPOSE 8000

CMD sh -c "php artisan migrate --force && php artisan db:seed --class=CategorySeeder && php artisan db:seed --class=AdminSeeder && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"
