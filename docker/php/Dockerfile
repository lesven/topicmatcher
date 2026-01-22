FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u 1000 -d /home/topicmatcher topicmatcher
RUN mkdir -p /home/topicmatcher/.composer && \
    chown -R topicmatcher:topicmatcher /home/topicmatcher

# Set working directory
WORKDIR /var/www

# Change ownership of our applications
RUN chown -R topicmatcher:topicmatcher /var/www

USER topicmatcher