<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    /** @use HasFactory<\Database\Factories\OrganizationFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'industry_type',
        'organization_type',
        'website',
        'address',
        'city',
        'state',
        'pincode',
        'country',
        'phone',
        'email',
        'description',
        'number_of_employees',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_by',
        'deleted_at',
        'user_owner_id',
    ];

    // Relationship: owner (user)
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_owner_id');
    }

    public function customers(){
        return $this->hasMany(Customer::class, 'organization_id');
    }

    // Relationship: industry
    public function industry()
    {
        return $this->belongsTo(Industry::class, 'industry_type');
    }

    // Relationship: people linked via pivot (multi-company contacts)
    public function people()
    {
        return $this->belongsToMany(Person::class, 'organization_person', 'organization_id', 'person_id')
            ->withTimestamps();
    }

    // Primary people still stored on people.organization_id (legacy / default)
    public function primaryPeople()
    {
        return $this->hasMany(Person::class, 'organization_id');
    }

    // Relationship: deals
    public function deals()
    {
        return $this->hasMany(Deal::class, 'organization_id');
    }

    // Relationship: leads
    public function leads()
    {
        return $this->hasMany(Lead::class, 'organization_id');
    }

    // Relationship: contact persons (legacy primary FK — used by mobile cleanup)
    public function contactPersons()
    {
        return $this->hasMany(Person::class, 'organization_id');
    }

    /**
     * People available for this company: pivot links + primary organization_id.
     */
    public function allContactPersons()
    {
        return Person::query()
            ->where(function ($q) {
                $q->where('organization_id', $this->id)
                    ->orWhereHas('organizations', function ($oq) {
                        $oq->where('organizations.id', $this->id);
                    });
            })
            ->orderBy('first_name');
    }
}
