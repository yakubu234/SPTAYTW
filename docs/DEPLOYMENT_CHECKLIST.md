# Production Checklist

- [ ] PHP 8.2+ selected for the domain
- [ ] Composer 2 available
- [ ] MySQL database and user created
- [ ] `.env` created only on the server
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` matches the HTTPS domain
- [ ] `APP_KEY` generated with `php artisan key:generate`
- [ ] API-Football key stored only in `.env`
- [ ] `storage` and `bootstrap/cache` writable
- [ ] `php artisan migrate --force` succeeds
- [ ] `php artisan route:list --path=football` succeeds
- [ ] `/up` responds successfully
- [ ] `/football` loads
- [ ] cPanel cron runs `artisan schedule:run`
- [ ] paper-test mode used before relying on screening results
