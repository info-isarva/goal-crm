<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialYear extends Model
{
    use HasFactory;

    protected $table = 'financial_years';

    protected $fillable = [
        'from_date',
        'to_date',
        'fin_key',
        'status',
        'active',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'active' => 'boolean',
    ];

    /**
     * Scope to get the active financial year
     */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }
}
