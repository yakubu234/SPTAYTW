<?php
use App\Contracts\Football\FootballDataProvider;
use App\Services\Football\Providers\ApiFootballProvider;
$this->app->bind(FootballDataProvider::class, ApiFootballProvider::class);
