<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; class VerificationDocument extends Model { public $timestamps=false; protected $primaryKey='document_id'; protected $fillable=['partner_id','document_type','file_path','document_status']; protected $casts=['submitted_at'=>'datetime']; public function partner(){return $this->belongsTo(PartnerProfile::class,'partner_id');} }


