<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StageHistory extends Model
{
    protected $table = 'stage_history';
    public $timestamps = false;
    protected $fillable = [
        'deal_id',
        'stage_name',
        'amount',
        'probability',
        'close_date',
        'modified_time',
        'modified_by',
    ];

    public function deal()
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
