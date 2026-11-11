<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'gender',
        'dob',
        'email',
        'phone',
        'mobile',
        'job_title',
        'lead_source',
        'user_owner_id',
        'organization_id',
        'customer_id',
        'linkedin',
        'address',
        'notes',
        'created_by',
        'created_at',
        'updated_at',
        'updated_by',
        'deleted_by',
        'deleted_at',
    ];

    // Add owner relationship (for user_owner_id field)
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_owner_id');
    }

    // Add organization relationship (primary / legacy)
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * Many companies can share the same contact person.
     */
    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_person', 'person_id', 'organization_id')
            ->withTimestamps();
    }

    /**
     * Attach this contact to a company without removing other company links.
     */
    public function attachToOrganization(int $organizationId): void
    {
        if ($organizationId <= 0) {
            return;
        }

        $this->organizations()->syncWithoutDetaching([$organizationId]);

        if (empty($this->organization_id)) {
            $this->organization_id = $organizationId;
            $this->save();
        }
    }

    /**
     * Detach this contact from a company; keep primary organization_id valid.
     */
    public function detachFromOrganization(int $organizationId): void
    {
        if ($organizationId <= 0) {
            return;
        }

        $this->organizations()->detach($organizationId);

        if ((int) $this->organization_id === (int) $organizationId) {
            $next = $this->organizations()->orderBy('name')->first();
            $this->organization_id = $next?->id;
            $this->save();
        }
    }

    /**
     * All linked companies (pivot + legacy primary).
     */
    public function allOrganizations()
    {
        $this->loadMissing(['organizations', 'organization']);

        $linked = $this->organizations;
        if ($this->organization && !$linked->contains('id', $this->organization->id)) {
            $linked = $linked->prepend($this->organization);
        }

        return $linked->unique('id')->sortBy('name')->values();
    }

    /**
     * Resolve a contact by full name, preferring one already linked to the company.
     */
    public static function findByNameForOrganization(string $fullName, ?int $organizationId = null): ?self
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return null;
        }

        $parts = explode(' ', $fullName, 2);
        $firstName = $parts[0] ?? '';
        $lastName = $parts[1] ?? '';

        $base = static::query()
            ->whereRaw('LOWER(first_name) = ?', [strtolower($firstName)])
            ->whereRaw('LOWER(COALESCE(last_name, "")) = ?', [strtolower($lastName)]);

        if ($organizationId) {
            $linked = (clone $base)->where(function ($q) use ($organizationId) {
                $q->where('organization_id', $organizationId)
                    ->orWhereHas('organizations', function ($oq) use ($organizationId) {
                        $oq->where('organizations.id', $organizationId);
                    });
            })->first();

            if ($linked) {
                return $linked;
            }
        }

        return $base->first();
    }

    // Relationship with leads
    public function leads()
    {
        return $this->hasMany(Lead::class, 'people_id');
    }

    // Relationship with deals
    public function deals()
    {
        return $this->hasMany(Deal::class, 'people_id');
    }

    //full name accessor for particular person
    public function getFullNameAttribute()
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }
}
