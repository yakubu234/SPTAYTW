<?php

namespace App\Console\Commands;

use App\Models\RacingAnalysis;
use Illuminate\Console\Command;

class RacingShortlist extends Command
{
    protected $signature = 'racing:shortlist {date? : YYYY-MM-DD}';
    protected $description = 'Show prediction-qualified horses and their recent historical evidence';

    public function handle(): int
    {
        $date = $this->argument('date') ?: now()->toDateString();
        $analyses = RacingAnalysis::with(['runner.race.meeting'])
            ->whereHas('race', fn($q) => $q->whereDate('off_time', $date))
            ->whereIn('status', ['strong','candidate','strong_qualified','qualified'])
            ->orderByDesc('score')->get();

        if ($analyses->isEmpty()) {
            $this->warn('NO PREDICTION-QUALIFIED RACES');
            return self::SUCCESS;
        }

        foreach ($analyses as $a) {
            $race=$a->runner->race; $e=$a->evidence ?? [];
            $this->newLine();
            $this->info(sprintf('%s %s — %s | %s | Score %d | Quality %d | Conf %s',
                $race->off_time->format('H:i'), $race->meeting->course, $a->runner->horse,
                strtoupper(str_replace('_',' ',$a->status)), $a->score, $a->data_quality, $a->race_confidence
            ));
            $runs=collect($e['recent_runs'] ?? []);
            if ($runs->isEmpty()) {
                $this->line('Recent-run detail not stored yet. Run racing:enrich for this date, then racing:analyse.');
                continue;
            }
            $this->table(['Date','Course','Pos','Field','Distance yds','Going','Surface','Class','SP'],
                $runs->map(fn($r)=>[
                    $r['date']??'-',$r['course']??'-',$r['position']??'-',$r['field_size']??'-',
                    $r['distance_yards']??'-',$r['going']??'-',$r['surface']??'-',$r['class']??'-',
                    isset($r['starting_price']) ? number_format((float)$r['starting_price'],2) : '-',
                ])->all()
            );
        }
        return self::SUCCESS;
    }
}
