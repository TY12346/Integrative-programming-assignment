<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class DeliveryTask extends Model { public $timestamps=false; protected $primaryKey='delivery_id'; protected $fillable=['reservation_id','volunteer_id','pickup_address','delivery_address','delivery_status','picked_up_at','delivered_at']; public function reservation(){return $this->belongsTo(Reservation::class,'reservation_id');} public function volunteer(){return $this->belongsTo(PartnerProfile::class,'volunteer_id');} public function statusHistories(){return $this->hasMany(DeliveryStatusHistory::class,'delivery_id');} }
