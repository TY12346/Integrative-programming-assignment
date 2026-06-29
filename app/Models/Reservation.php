<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class Reservation extends Model { public $timestamps=false; protected $primaryKey='reservation_id'; protected $fillable=['request_id','donation_id','reserved_quantity','reservation_status','pickup_deadline']; protected $casts=['pickup_deadline'=>'datetime']; public function request(){return $this->belongsTo(FoodRequest::class,'request_id');} public function donation(){return $this->belongsTo(FoodDonation::class,'donation_id');} public function deliveryTask(){return $this->hasOne(DeliveryTask::class,'reservation_id');} }
