#!/bin/sh
# Dijalankan setiap kali container Render menyala.
set -e

# Render memberi nomor port lewat variabel PORT
PORT="${PORT:-10000}"
sed -ri "s/Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Folder kerja Laravel (bisa kosong karena tidak ikut di image)
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Koneksi ke database Aiven memakai SSL (DB_SSLMODE=require di pengaturan Render)

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

# Data demo disegarkan setiap kali server menyala, HANYA jika variabel DEMO_SEGARKAN=true
# diisi di Environment Render. Hanya data bertanda demo yang dibuat ulang; data input petugas aman.
# Kalau gagal (mis. data demo belum ada), server tetap jalan.
if [ "${DEMO_SEGARKAN}" = "true" ]; then
    php artisan demo:segarkan --force || echo "demo:segarkan dilewati"
fi

exec apache2-foreground
