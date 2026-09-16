<?php

namespace App\Providers;

use App\Contracts\Football\FootballDataProvider;
use App\Services\Analysis\MarketStatusResolver;
use App\Services\Football\Providers\ApiFootballProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FootballDataProvider::class, ApiFootballProvider::class);

        $this->app->singleton(MarketStatusResolver::class, fn () =>
            new MarketStatusResolver((int) config('football.thresholds.minimum_data_quality', 55))
        );
    }

    public function boot(): void
    {
        //
    }
}
