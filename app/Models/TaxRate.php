<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use HasFactory;

    protected $table = 'tax_rates';

    protected $fillable = [
        'name',
        'rate',
        'type',
        'country',
        'created_by',
        'created_at',
        'updated_at',
        'updated_by',
        'deleted_by',
        'deleted_at',
    ];

    public $timestamps = false;
}
