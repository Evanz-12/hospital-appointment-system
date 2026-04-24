FROM dunglas/frankenphp:latest-php8.2

RUN install-php-extensions mysqli mbstring openssl pdo_mysql

WORKDIR /app
COPY . .
