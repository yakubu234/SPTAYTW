# cPanel Shared Hosting Deployment

This application supports a document root that contains the whole repository. The root `.htaccess` blocks Laravel internals and forwards public requests to `public/index.php`.

## Requirements
- PHP 8.2 or newer (PHP 8.3 recommended)
- Composer 2
- MySQL/MariaDB
- PHP extensions required by Laravel, including cURL, Mbstring, OpenSSL, PDO MySQL, XML and Fileinfo
- cPanel Terminal or SSH is recommended

## First deployment
```bash
cd ~/public_html
git clone https://github.com/yakubu234/SPTAYTW.git .
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Edit `.env` directly on the server. Set `APP_URL`, database credentials and `API_FOOTBALL_KEY`. Never commit `.env`.

Then:
```bash
chmod -R 775 storage bootstrap/cache
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Open `/up` first. A successful response confirms Laravel boots. Then open `/football`.

## cPanel Cron Job
Use the server's absolute PHP binary and application path. Example only:
```cron
* * * * * cd /home/CPANEL_USER/public_html && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

Do not copy the example paths blindly. Run `which php` and `pwd` in cPanel Terminal and use those exact values.

The Laravel scheduler runs `football:daily` at 07:00 and 23:30 application time. `APP_TIMEZONE` defaults to `Africa/Lagos` in the supplied environment template.

## Updating
```bash
cd ~/public_html
git pull origin master
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Security
- Keep `.env` out of Git.
- Never store API-Football or hosting credentials in repository files.
- Keep `APP_DEBUG=false` in production.
- The root `.htaccess` denies HTTP access to application, configuration, storage, vendor and environment files.
- If cPanel lets you set the domain document root directly to `public_html/public`, prefer that standard Laravel layout; the root compatibility `.htaccess` is for hosts where the repository itself must live in the web root.
