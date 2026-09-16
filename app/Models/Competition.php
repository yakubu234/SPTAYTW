<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Competition extends Model { protected $fillable=['provider_id','name','country','type','high_variance']; protected $casts=['high_variance'=>'boolean']; }
