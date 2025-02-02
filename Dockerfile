FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    nodejs \
    npm \
    sqlite3 \
    libsqlite3-dev \
    libxml2-dev \
    zip \
    unzip \
    psmisc \
    && apt-get clean

# Install Ollama
RUN curl https://ollama.ai/install.sh | sh

# Debug: Verify Ollama is installed
RUN echo "Checking if Ollama is installed..." && \
    which ollama && \
    ollama --version

# Install PHP extensions
RUN docker-php-ext-install pdo_sqlite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

# Install PHP dependencies
RUN composer install

# Install Node dependencies
RUN npm install

# Pull the Ollama model (this happens last)
RUN ollama serve & sleep 5 && ollama pull deepseek-r1:8b && killall ollama
