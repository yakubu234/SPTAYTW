# Sporty Safe Engine V5

Laravel-ready football **screening and validation** application. “Safe” is a project label only: no football selection is guaranteed. Model scores are evidence/rule scores, not win probabilities.

## V5 additions
- `football:daily` end-to-end sync → analyse → grade command
- Laravel scheduler example for automatic morning screening and post-match refresh
- manual SportyBet decimal-odds capture per analysis
- bookmaker implied probability stored separately from model score
- bankroll + stake recording on generated tickets
- stake exposure and potential-return calculation when all odds are entered
- ticket history page
- calibration report by market and model-score band
- CLI calibration: `php artisan football:calibration --days=90`
- conservative risk warning service; >2% of recorded bankroll is flagged while the model is being validated

## Existing engine
- Team Over 0.5
- Under 4.5
- Home / Away
- Team-or-GG
- API-Football fixture/history integration
- data-quality and competition-volatility penalties
- contradiction-aware explainable scoring
- automatic full-time grading
- shortlist/ticket builder with one selection per fixture and league caps
- browser dashboard and performance pages

## Install into Laravel 11/12
Copy/merge `app`, `config`, `database`, `resources` and `routes/football.php` into a Laravel project. Add to `routes/web.php`:

```php
require __DIR__.'/football.php';
```

Merge `.env.example.additions` into `.env`, bind `FootballDataProvider` using `app/Providers/AppServiceProvider.example.php`, then:

```bash
php artisan migrate
php artisan serve
```

For scheduling, merge `routes/console.php.example` into `routes/console.php`, then ensure the normal Laravel scheduler is running on the server:

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Daily workflow

```bash
php artisan football:daily 2026-09-16
php artisan football:calibration --days=90
```

Or open `/football` and use the dashboard.

Useful pages:
- `/football` — daily screening
- `/football/tickets` — generated ticket history
- `/football/performance` — market performance
- `/football/calibration` — observed score-band calibration

## Validation rule
Run in paper-test mode first. Do not interpret an 85 model score as an 85% probability. Calibration only becomes useful after a meaningful number of independently graded selections have accumulated.

## Still intentionally excluded
- automatic SportyBet login or bet placement
- scraping SportyBet behind authentication
- automatic “major attacker” classification without reliable player minutes/starts/position data
- automatic threshold changes based on tiny samples

## Recommended V6
1. player-impact enrichment from position + minutes + starts + goal involvement
2. competition/market-specific calibration with minimum-sample safeguards
3. daily shortlist notification/export (CSV/PDF)
4. stronger backtesting command over historical fixture windows
5. deployment package for Ubuntu/Nginx/Supervisor/cron
