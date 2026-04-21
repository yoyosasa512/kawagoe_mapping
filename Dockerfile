FROM php:8.2-apache

# PostgreSQLクライアントライブラリのインストール
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# ソースコードをコンテナ内にコピー
COPY . /var/www/html/

# Apacheのドキュメントルートの権限設定
RUN chown -R www-data:www-data /var/www/html