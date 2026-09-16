# Sporty Safe Engine V6

Laravel-ready football screening and validation application. “Safe” is a project label only: no football selection is guaranteed. Model scores are evidence/rule scores, not win probabilities.

## Markets
- Team Over 0.5 goals
- Under 4.5 match goals
- Home / Away outright
- Team-or-GG with conservative independent-route validation
- Draw No Bet is intentionally excluded.

## V6
V6 completes the repository baseline and adds minimum-sample calibration safeguards, historical result backtesting, shortlist CSV export and deployment notes. Automatic player-impact penalties remain disabled until reliable minutes/starts/position data is available; the engine must not guess player importance.

## Daily workflow
```bash
php artisan football:daily 2026-09-16
php artisan football:calibration --days=90
php artisan football:backtest --days=90
php artisan football:export-shortlist 2026-09-16 --minimum=85 --quality=80
```

## Validation
Run in paper-test mode first. A model score of 85 is not an 85% probability. Observed calibration is based only on graded selections. Small samples are explicitly marked insufficient.

## Environment
```env
FOOTBALL_PROVIDER=api-football
API_FOOTBALL_KEY=
API_FOOTBALL_BASE_URL=https://v3.football.api-sports.io
```

## Integrity rules
No automatic SportyBet login/bet placement; no credentials; bookmaker odds are not safety evidence; high-variance competitions are penalised; low data quality is SKIP; generated tickets use one selection per fixture and are never force-filled.
