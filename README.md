# Sporty Safe Engine V6

Laravel-ready football screening and validation application. “Safe” is a project label only: no football selection is guaranteed. Model scores are evidence/rule scores, not win probabilities.

## Markets
- Team Over 0.5 goals
- Under 4.5 match goals
- Home / Away outright
- Team-or-GG (conservative independent-route validation)
- Draw No Bet is intentionally excluded.

## V6 focus
V6 completes the repository baseline and adds safer calibration rules, historical backtesting support, shortlist CSV export, and deployment documentation. Automatic player-impact penalties remain disabled until reliable minutes/starts/position data is available; the engine must not guess player importance.

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
Merge `app`, `config`, `database`, `resources`, and `routes` into Laravel 11/12. Register `FootballDataProvider` to `ApiFootballProvider`, run migrations, and configure the scheduler.

Useful pages:
- `/football` daily screening
- `/football/tickets` ticket history
- `/football/performance` observed market performance
- `/football/calibration` score-band calibration

## Safety / integrity rules
- no automatic SportyBet login or bet placement
- no SportyBet credential collection
- no bookmaker odds treated as evidence of safety
- youth/reserve/friendly competitions are penalised/excluded by default
- data quality below the configured floor is SKIP
- one selection per fixture in generated tickets
- no forced ticket filling
- no automatic player-importance claims without sufficient provider data
