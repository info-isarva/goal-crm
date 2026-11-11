<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    use HasFactory;

    protected $casts = [
        'created_at' => 'datetime',
    ];
    use HasFactory;

    protected $table = 'deals';

    protected $fillable = [
        'title',
        'description',
        'amount',
        'label',
        'lead_source',
        'referral_name',
        'category',
        'main_category_id',
        'industry_id',
        'organization_id',
        'customer_id',
        'people_id',
        'value',
        'stage_id',
        'reason_for_loss',
        'probability', // Added probability field
        'status',
        'close_date',
        'assigned_id',
        'user_owner_id',
        'created_by',
        'created_at',
        'updated_at',
        'updated_by',
        'deleted_by',
        'deleted_at',
        'converted_lead_id',
    ];

    public $timestamps = false;

    protected $appends = ['person_name', 'organization_name', 'stage_name'];

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
     * Get the deal source model associated with the lead.
     */
    public function dealSource()
    {
        return $this->belongsTo(LeadSource::class, 'lead_source');
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class, 'stage_id');
    }

    /**
     * Convenience accessor for templates that expect a stage name string.
     */
    public function getStageNameAttribute(): ?string
    {
        if ($this->relationLoaded('stage')) {
            return optional($this->getRelation('stage'))->name;
        }

        if (!empty($this->attributes['stage_id'])) {
            return Stage::nameById($this->attributes['stage_id']);
        }

        return null;
    }

    /**
     * Get the category associated with the deal.
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

    public function quotations()
    {
        return $this->hasMany(Quotation::class, 'deal_id');
    }

    /**
     * Get the person (contact) fullname associated with the lead.
     */
    public function getPersonNameAttribute()
    {
        return $this->person 
            ? $this->person->first_name . ' ' . $this->person->last_name 
            : '';
    }

    public function getOrganizationNameAttribute()
    {
        return $this->organization ? $this->organization->name : '';
    }


}
