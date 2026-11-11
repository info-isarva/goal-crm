<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'job_title',
        'organization_id',
        'user_owner_id', // use owner_id for consistency
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_by',
        'deleted_at',
        'created_at',
    ];


    public function people()
    {
        return $this->hasMany(\App\Models\Person::class);
    }

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }

    public function owner()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_owner_id');
    }

    /**
     * Get customer name by id (static helper for views)
     */
    public static function getNameById($id)
    {
        return static::find($id)?->name ?? '-';
    }
}
