FROM php:8.3-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    libpq-dev

# Install Node.js for Vite/frontend builds
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP extensions: use mlocati installer so dom/xml/xmlreader build order and deps are correct
# (plain docker-php-ext-install can hit "ext/dom/dom_ce.h: No such file" for xmlreader).
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    pdo_mysql pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip intl \
    dom xml simplexml xmlreader xmlwriter

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Update Apache DocumentRoot to point to Laravel's public directory
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set working directory
WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy the application code
COPY . .

# Set proper permissions for Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Install dependencies and build assets
#
# Analysis (exit code 2 on this RUN):
# - .env.example sets SESSION_DRIVER/CACHE_STORE/QUEUE_CONNECTION=database. During `docker build`
#   there is no MySQL; bootstrapping for `package:discover` / `config:clear` can hit the DB layer and fail.
# - Composer is run with --no-scripts so post-autoload-dump does not run before .env + APP_KEY exist.
# - For Artisan only, prefix with env(...) so those vars override .env for that process (Dotenv does not
#   overwrite existing environment variables). The on-disk .env stays suitable for runtime with MySQL.
RUN composer install --no-interaction --optimize-autoloader --no-dev --no-scripts \
    && ([ -f .env ] || cp .env.example .env) \
    && env SESSION_DRIVER=file CACHE_STORE=file QUEUE_CONNECTION=sync \
        php artisan key:generate --force --no-interaction \
    && env SESSION_DRIVER=file CACHE_STORE=file QUEUE_CONNECTION=sync \
        php artisan package:discover --ansi --no-interaction \
    && env SESSION_DRIVER=file CACHE_STORE=file QUEUE_CONNECTION=sync \
        php artisan config:clear --no-interaction
RUN npm install
RUN npm run build

# Expose port 80
EXPOSE 80

# Start Apache with auto migration and seeding
CMD bash -c "php artisan migrate --force && php artisan db:seed --class=AdminSeeder --force && apache2-foreground"
