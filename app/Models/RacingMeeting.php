<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RacingMeeting extends Model {
    protected $fillable=['provider_id','course','country','meeting_date','going'];
    protected $casts=['meeting_date'=>'date'];
    public function races(){ return $this->hasMany(RacingRace::class,'meeting_id'); }
}