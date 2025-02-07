# Stage 1: Build the Node.js dependencies
FROM node:18 AS build

WORKDIR /style-finder

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

WORKDIR /style-finder

# Copy built node_modules and application code
COPY --from=build /style-finder /style-finder

# Install PHP dependencies
RUN composer install

# Simple path setup
ENV PATH /style-finder/node_modules/.bin:$PATH

# Ollama setup
RUN ollama serve & \
    sleep 10 && \
    echo "Server should be ready" && \
    ollama pull llama3.2 && \
    echo "Pull completed" && \
    killall ollama && \
    echo "Ollama server stopped and model pulled"