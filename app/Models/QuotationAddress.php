<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationAddress extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'quotation_id',
        'address_type',
        'flat_no',
        'address_line1',
        'city',
        'state',
        'country',
        'pincode',
    ];

    /**
     * Get the quotation that owns the address.
     */
    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }
}