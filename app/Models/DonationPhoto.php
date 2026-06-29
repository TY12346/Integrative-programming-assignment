<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; class DonationPhoto extends Model { public $timestamps=false; protected $primaryKey='photo_id'; protected $fillable=['donation_id','file_path']; public function donation(){return $this->belongsTo(FoodDonation::class,'donation_id');} }
