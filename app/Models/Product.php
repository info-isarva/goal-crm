<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'product_name',
        'product_code',
        'product_type',
        'product_description',
        'project_detail',
        'unit_price',
        'product_status',
        'product_category_id',
        'commission_rate',
        'tax_status',
        'product_owner_id',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'deleted_by',
        'deleted_at',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'product_owner_id');
    }

    public function taxRate()
    {
        return $this->belongsToMany(TaxRate::class, 'product_tax', 'product_id', 'tax_id');
    }
}
