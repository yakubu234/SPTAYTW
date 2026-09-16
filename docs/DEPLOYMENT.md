# Ubuntu / Nginx deployment

Use a normal Laravel 11/12 deployment. Configure PHP-FPM, Nginx, MySQL, the Laravel scheduler and storage permissions. Keep `API_FOOTBALL_KEY` only in `.env`; never commit it.

Scheduler cron:
```cron
* * * * * cd /var/www/sptaytw && php artisan schedule:run >> /dev/null 2>&1
```

Recommended pre-production sequence:
1. run migrations;
2. configure API-Football key;
3. run `php artisan football:daily` manually;
4. confirm fixture and analysis records;
5. keep paper-testing until calibration has meaningful samples;
6. only then evaluate operational use.
