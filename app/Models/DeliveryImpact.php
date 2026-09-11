<?php
/**
 * FoodLink - Module 4 Delivery & Impact Tracking
 * Author : KHOO SHENG HAO
 * File   : app/Models/DeliveryImpact.php
 * Purpose: Stores impact records produced automatically when deliveries complete.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryImpact extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'impact_id';

    protected $fillable = [
        'delivery_id',
        'impact_type',
        'quantity',
        'measurement_unit',
        'description',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function delivery()
    {
        return $this->belongsTo(DeliveryTask::class, 'delivery_id');
    }
}
