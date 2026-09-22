<?php
namespace App\Services\Racing;
use App\Models\{RacingMeeting,RacingRace,RacingRunner};
use Carbon\Carbon;

class RacingImporter {
    public function __construct(private RacingApiClient $api){}
    public function importDate(string $date): array {
        $payload=$this->api->racecards($date); $races=$payload['racecards'] ?? $payload['races'] ?? $payload['data'] ?? [];
        $raceCount=0; $runnerCount=0;
        foreach($races as $row){
            $course=$row['course'] ?? $row['venue'] ?? 'Unknown';
            $meeting=RacingMeeting::updateOrCreate(
                ['provider_id'=>(string)($row['meeting_id'] ?? $date.'-'.$course)],
                ['course'=>$course,'country'=>$row['region'] ?? $row['country'] ?? null,'meeting_date'=>$date,'going'=>$row['going'] ?? null]
            );
            $race=RacingRace::updateOrCreate(
                ['provider_id'=>(string)($row['race_id'] ?? $row['id'] ?? sha1(json_encode([$date,$course,$row['off_time'] ?? null,$row['race_name'] ?? null])))],
                ['meeting_id'=>$meeting->id,'name'=>$row['race_name'] ?? $row['name'] ?? 'Race','off_time'=>$this->offTime($date,$row['off_time'] ?? $row['off'] ?? null),
                 'distance_yards'=>$row['distance_yards'] ?? null,'race_class'=>$row['race_class'] ?? $row['class'] ?? null,
                 'surface'=>$row['surface'] ?? null,'status'=>$row['status'] ?? 'scheduled','field_size'=>count($row['runners'] ?? [])]
            ); $raceCount++;
            foreach(($row['runners'] ?? []) as $runner){
                RacingRunner::updateOrCreate(
                    ['race_id'=>$race->id,'provider_id'=>(string)($runner['horse_id'] ?? $runner['id'] ?? sha1((string)($runner['horse'] ?? $runner['name'] ?? 'unknown')))],
                    ['horse'=>$runner['horse'] ?? $runner['name'] ?? 'Unknown','jockey'=>$runner['jockey'] ?? null,'trainer'=>$runner['trainer'] ?? null,
                     'draw'=>$runner['draw'] ?? null,'weight_lbs'=>$runner['weight_lbs'] ?? null,'official_rating'=>$runner['official_rating'] ?? $runner['or'] ?? null,
                     'speed_rating'=>$runner['speed_rating'] ?? null,'performance_rating'=>$runner['performance_rating'] ?? null,
                     'decimal_odds'=>$runner['decimal_odds'] ?? null,'non_runner'=>(bool)($runner['non_runner'] ?? false)]
                ); $runnerCount++;
            }
        }
        return ['races'=>$raceCount,'runners'=>$runnerCount];
    }
    private function offTime(string $date, mixed $time): Carbon {
        if (!$time) return Carbon::parse($date.' 12:00:00');
        return str_contains((string)$time,'-') ? Carbon::parse($time) : Carbon::parse($date.' '.$time);
    }
}