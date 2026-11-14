#!/bin/sh
# Dijalankan setiap kali container Render menyala.
set -e

# Render memberi nomor port lewat variabel PORT
PORT="${PORT:-10000}"
sed -ri "s/Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Koneksi ke database Aiven memakai SSL (DB_SSLMODE=require di pengaturan Render)

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

exec apache2-foreground
