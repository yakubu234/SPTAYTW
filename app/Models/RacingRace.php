<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RacingRace extends Model {
    protected $fillable=['meeting_id','provider_id','name','off_time','distance_yards','race_class','surface','status','field_size'];
    protected $casts=['off_time'=>'datetime'];
    public function meeting(){return $this->belongsTo(RacingMeeting::class,'meeting_id');}
    public function runners(){return $this->hasMany(RacingRunner::class,'race_id');}
    public function analyses(){return $this->hasMany(RacingAnalysis::class,'race_id');}
}