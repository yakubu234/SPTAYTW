# First-five-minute goals

The `goal_first_5` screen is separate from the regular goals and corners markets. It reads timestamped goals from the last ten completed matches of each team, reports the matching home and away samples, and prints every playable fixture with its evidence. WATCH and SKIP are research labels, not probabilities or guarantees. Missing/incomplete provider timelines remain unknown.

After deploying this branch and migrating:

```bash
/opt/alt/php83/usr/bin/php artisan migrate --force
/opt/alt/php83/usr/bin/php artisan football:sync-fixtures YYYY-MM-DD
/opt/alt/php83/usr/bin/php artisan football:sync-early-goals YYYY-MM-DD --limit=100
/opt/alt/php83/usr/bin/php artisan football:early-goals YYYY-MM-DD
```

Repeat the history sync until it reports zero unchecked matches. Use `--retry-missing` only when the provider may now have event records for previously incomplete matches. `football:grade YYYY-MM-DD` refreshes completed fixtures and grades verified early-goal outcomes.

## Automatic preparation at 10:00 PM WAT

`routes/console.php` schedules `football:prepare-tomorrow` daily at 22:00 Africa/Lagos. It syncs tomorrow's fixtures, up to 100 corner and 100 early-goal history requests, then runs the regular and early-goal analyses. It does not submit bets. To run it manually:

```bash
/opt/alt/php83/usr/bin/php artisan football:prepare-tomorrow
```

The schedule requires the server's cron to invoke Laravel every minute. In cPanel, use the application's **actual absolute path** in place of `/path/to/app`:

```cron
* * * * * cd /path/to/app && /opt/alt/php83/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Check `php artisan schedule:list` after deployment. A run capped by the history limit can leave unchecked matches; repeat `football:sync-early-goals YYYY-MM-DD` and `football:sync-corners YYYY-MM-DD` if the output shows remaining history, then rerun both analyses.
