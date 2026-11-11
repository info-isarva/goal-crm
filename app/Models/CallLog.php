<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CallLog extends Model
{
    use SoftDeletes;

    protected $table = 'call_logs';

    protected $fillable = [
        'name',
        'company_name',
        'address',
        'mobile_number',
        'email',
        'requirement',
        'estimated_budget',
        'call_status',
        'lead_status',
        'next_follow_up_date',
        'next_action',
        'remarks',
        'source',
        'type',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
