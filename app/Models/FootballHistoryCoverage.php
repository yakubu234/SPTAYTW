<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class FootballHistoryCoverage extends Model
{
    protected $fillable = ['team_provider_id', 'season', 'fetched_at'];

    protected $casts = [
        'fetched_at' => 'datetime',
    ];
}
