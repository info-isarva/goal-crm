<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoalCustomerParty extends Model
{
    protected $table = 'goal_customer_parties';

    protected $fillable = [
        'normalized_mobile',
        'canonical_name',
    ];

    public function leads(): HasMany
    {
        return $this->hasMany(GoalLoanLead::class, 'goal_party_id');
    }

    public function cases(): HasMany
    {
        return $this->hasMany(GoalLoanCase::class, 'goal_party_id');
    }
}
