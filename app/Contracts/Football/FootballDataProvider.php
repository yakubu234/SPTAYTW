<?php
namespace App\Contracts\Football;
interface FootballDataProvider {
    public function fixtures(string $date): array;
    public function teamFixtures(int $teamId, int $last = 10): array;
    public function injuries(int $fixtureId): array;
}
