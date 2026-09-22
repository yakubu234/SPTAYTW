<?php
use App\Http\Controllers\RacingDashboardController; use Illuminate\Support\Facades\Route;
Route::prefix('racing')->name('racing.')->group(function(){Route::get('/',[RacingDashboardController::class,'index'])->name('dashboard');});
