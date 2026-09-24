<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RacingRunner extends Model {
    protected $fillable=['race_id','provider_id','horse','jockey','trainer','draw','weight_lbs','official_rating','speed_rating','performance_rating','decimal_odds','historical_evidence','non_runner','finish_position'];
    protected $casts=['non_runner'=>'boolean','decimal_odds'=>'decimal:3','historical_evidence'=>'array'];
    public function race(){return $this->belongsTo(RacingRace::class,'race_id');}
}
