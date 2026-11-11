<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadStatus extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'leads_status';

    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
