# Sporty Safe Engine V6

Laravel-ready football screening and validation application. “Safe” is a project label only: no football selection is guaranteed. Model scores are evidence/rule scores, not win probabilities.

## Markets
- Team Over 0.5 goals
- Under 4.5 match goals
- Home / Away outright
- Team-or-GG (conservative independent-route validation)
- Draw No Bet is intentionally excluded.

## V6 focus
V6 completes the repository baseline and adds safer calibration rules, historical backtesting support, shortlist CSV export, dashboard views, and deployment documentation. Automatic player-impact penalties remain disabled until reliable minutes/starts/position data is available; the engine must not guess player importance.

## Daily workflow
```bash
php artisan football:daily 2026-09-16
php artisan football:calibration --days=90
php artisan football:backtest --days=90
php artisan football:export-shortlist 2026-09-16 --minimum=85 --quality=80
```

## Validation
Run in paper-test mode first. A model score of 85 is not an 85% probability. Observed calibration is reported only from graded selections and V6 marks small samples as insufficient rather than recommending automatic threshold changes.

## Environment
```env
FOOTBALL_PROVIDER=api-football
API_FOOTBALL_KEY=
API_FOOTBALL_BASE_URL=https://v3.football.api-sports.io
```

## Laravel integration
This repository contains the football module rather than a complete Laravel framework skeleton. Merge `app`, `config`, `database`, `resources`, and `routes` into Laravel 11/12.

Register `FootballDataProvider` to `ApiFootballProvider` using the provider example, and include the football routes from `routes/web.php`:
```php
require __DIR__.'/football.php';
```

Then run:
```bash
php artisan migrate
php artisan route:list --path=football
php artisan test
php artisan serve
```

Useful pages:
- `/football` daily screening and ticket builder
- `/football/tickets` ticket history
- `/football/performance` observed market performance
- `/football/calibration` score-band calibration

## Server scheduling
Merge `routes/console.php.example` into the Laravel application's `routes/console.php`, then run the normal Laravel scheduler from cron:
```cron
* * * * * cd /var/www/sporty-safe-engine && php artisan schedule:run >> /dev/null 2>&1
```
See `docs/DEPLOYMENT_UBUNTU.md` for Nginx/PHP-FPM deployment notes.

## Safety / integrity rules
- no automatic SportyBet login or bet placement
- no SportyBet credential collection
- no bookmaker odds treated as evidence of safety
- youth/reserve/friendly competitions are penalised/excluded by default
- data quality below the configured floor is SKIP
- one selection per fixture in generated tickets
- no forced ticket filling
- no automatic player-importance claims without sufficient provider data
