<?php
/**
 * FoodLink - Module 4 Delivery & Impact Tracking
 * Author : KHOO SHENG HAO
 * File   : app/Models/DeliveryApiRequest.php
 * Purpose: Replay-protection ledger for HMAC-authenticated delivery API requests.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryApiRequest extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'request_log_id';

    protected $fillable = [
        'request_id',
        'request_timestamp',
        'http_method',
        'request_path',
        'ip_address',
    ];

    protected $casts = [
        'request_timestamp' => 'integer',
        'processed_at' => 'datetime',
    ];
}
