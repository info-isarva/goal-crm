<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Call extends Model
{
    use SoftDeletes;

    protected $fillable = [
       'name',
        'description',
        'start_at',
        'finish_at',
        'location',
        'related_type',
        'related_id',
        'user_restored_id',
        'user_owner_id',
        'user_assigned_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
    ];

    protected $dates = ['start_at', 'finish_at', 'created_at', 'updated_at', 'deleted_at'];

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
