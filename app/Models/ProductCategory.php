<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends Model
{
    use SoftDeletes;

    protected $table = 'product_categories';

    protected $fillable = [
        'category_name',
        'description',
        'type',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function leads()
    {
        return $this->hasMany(Lead::class, 'category');
    }

    public function deals()
    {
        return $this->hasMany(Deal::class, 'category');
    }
}
