<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class FoodCategory extends Model { public $timestamps=false; protected $primaryKey='category_id'; protected $fillable=['category_name','description']; public function donations(){return $this->hasMany(FoodDonation::class,'category_id');} public function requests(){return $this->hasMany(FoodRequest::class,'category_id');} }
