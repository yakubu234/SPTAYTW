<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('football.dashboard'));

require __DIR__.'/football.php';
