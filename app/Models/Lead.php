<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $casts = [
        'converted_at' => 'datetime',
    ];
    /** @use HasFactory<\Database\Factories\LeadFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'organization_id',
        'customer_id',
        'people_id',
        'amount',
        'status',
        'reason_for_loss',
        'label',
        'expected_close',
        'converted_at',
        'description',
        'lead_source',
        'referral_name',
        'category',
        'main_category_id',
        'industry_id',
        'assigned_id',
        'user_owner_id',
        'created_by',
        'created_at',
        'updated_at',
        'updated_by',
        'deleted_by',
        'deleted_at',

    ];
    /**
     * Get the owner (user) of the lead.
     */
    public function owner()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_owner_id');
    }

    /**
     * Get the customer associated with the lead.
     */
    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id');
    }

    /**
     * Get the organization associated with the lead.
     */
    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class, 'organization_id');
    }

    /**
     * Get the person (contact) associated with the lead.
     */
    public function person()
    {
        return $this->belongsTo(\App\Models\Person::class, 'people_id');
    }

    /**
     * Get the lead source model associated with the lead.
     */
    public function leadSource()
    {
        return $this->belongsTo(LeadSource::class, 'lead_source');
    }

    /**
     * Get the category associated with the lead.
     */
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category', 'id');
    }

    public function mainCategory()
    {
        return $this->belongsTo(MainCategory::class, 'main_category_id');
    }

    public function industry()
    {
        return $this->belongsTo(Industry::class, 'industry_id');
    }
}
