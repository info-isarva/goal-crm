<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'content',
        'related_type',
        'related_id',
        'pinned',
        'noted_at',
        'user_restored_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $dates = ['noted_at', 'created_at', 'updated_at', 'deleted_at'];
}
