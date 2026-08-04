<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Models/RequestStatusHistory.php
 * Purpose: Eloquent model for the audit trail behind "Check Request Status".
 *          Every lifecycle change of a food request is appended here, including
 *          the automatic changes triggered by other modules.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestStatusHistory extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'request_history_id';

    protected $fillable = [
        'request_id',
        'old_status',
        'new_status',
        'changed_by',
        'remarks',
    ];

    protected $casts = ['changed_at' => 'datetime'];

    public function request()
    {
        return $this->belongsTo(FoodRequest::class, 'request_id');
    }

    /** Null when the change was made automatically by the system. */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function getActorNameAttribute(): string
    {
        return $this->changedBy?->full_name ?? 'System';
    }
}
