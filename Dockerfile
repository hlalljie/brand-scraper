# Stage 1: Build the Node.js dependencies
FROM node:18 AS build

WORKDIR /var/www

COPY package.json package-lock.json ./
RUN npm install --legacy-peer-deps

COPY . .

# Stage 2: Runtime environment
FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    sqlite3 \
    libsqlite3-dev \
    libxml2-dev \
    zip \
    unzip \
    psmisc \
    nodejs \
    npm \
    && apt-get clean

# Install Ollama
RUN curl https://ollama.ai/install.sh | sh

# Install PHP extensions
RUN docker-php-ext-install pdo_sqlite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy built node_modules and application code
COPY --from=build /var/www /var/www

# Install PHP dependencies
RUN composer install

# Simple path setup
ENV PATH /var/www/node_modules/.bin:$PATH

# Ollama setup
RUN ollama serve & \
    sleep 10 && \
    ollama pull deepseek-r1:8b && \
    killall ollama && \
    echo "Ollama server stopped and model pulled"