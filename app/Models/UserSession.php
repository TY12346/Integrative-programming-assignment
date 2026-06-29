<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; class UserSession extends Model { public $timestamps=false; protected $primaryKey='session_id'; protected $fillable=['user_id','session_token','ip_address','user_agent','logout_at','session_status']; public function user(){return $this->belongsTo(User::class,'user_id');} }
