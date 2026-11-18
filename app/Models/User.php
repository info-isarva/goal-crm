<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    public function userDetail()
    {
        return $this->hasOne(UserDetail::class, 'user_id');
    }

    public function orgUnit()
    {
        return $this->belongsTo(GoalOrgUnit::class, 'goal_org_unit_id');
    }

    public function manager()
    {
        return $this->belongsTo(self::class, 'reports_to_user_id');
    }

    public function directReports()
    {
        return $this->hasMany(self::class, 'reports_to_user_id');
    }

    /**
     * Org unit IDs this user may see (own unit + descendants for managers).
     */
    public function goalScopedOrgUnitIds(): array
    {
        if ($this->isSuperAdmin() || in_array((int) ($this->crm_role_type ?? -1), [1, 4, 5], true)) {
            return GoalOrgUnit::query()->pluck('id')->all();
        }

        if (!$this->goal_org_unit_id) {
            return [];
        }

        $unit = $this->orgUnit;
        if (!$unit) {
            return [(int) $this->goal_org_unit_id];
        }

        // RD / Cluster Head / BM see subtree; branch staff see own branch.
        if (in_array($unit->type, [GoalOrgUnit::TYPE_REGION, GoalOrgUnit::TYPE_CLUSTER], true)
            || in_array($this->job_role, ['rd', 'cluster', 'bm'], true)
            || in_array((int) $this->crm_role_type, [6, 7, 8], true)
        ) {
            return $unit->descendantIds();
        }

        return [(int) $this->goal_org_unit_id];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'address',
        'mobile',
        'city',
        'state',
        'country',
        '2fa_enabled',
        'sales_target',
        'crm_page_right',
        'crm_role_type',
        'goal_org_unit_id',
        'reports_to_user_id',
        'job_role',
        'google_id',
        'google_token',
        'google_refresh_token',
        'avatar',
        'email_compose_font_family',
        'email_compose_font_size',
        'email_signature_name',
        'email_signature_html',
        'compliance_consent_agreed',
        'compliance_consent_agreed_at',
        'fcm_token',
        'device_platform'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_token',
        'google_refresh_token',
        'fcm_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'compliance_consent_agreed' => 'boolean',
            'compliance_consent_agreed_at' => 'datetime',
        ];
    }


    /**
     * Goal ↔ classic CRM guard equivalents.
     * Goal seeds manage_users_guard / manage_roles_guard, while Users / Roles /
     * Audit controllers still check manage_crm_* / view_crm_activity_log_guard.
     * Directed: key = guard being checked, values = other rights that also grant it.
     *
     * @return list<string>
     */
    public static function crmPermissionEquivalents(string $permission): array
    {
        static $map = [
            // Users
            'manage_crm_user_guard' => ['manage_users_guard'],
            'view_crm_user_guard' => ['manage_users_guard'],
            'create_crm_user_guard' => ['manage_users_guard'],
            'edit_crm_user_guard' => ['manage_users_guard'],
            'manage_users_guard' => ['manage_crm_user_guard'],
            // Roles
            'manage_crm_role_guard' => ['manage_roles_guard'],
            'create_crm_role_guard' => ['manage_roles_guard'],
            'edit_crm_role_guard' => ['manage_roles_guard'],
            'manage_roles_guard' => ['manage_crm_role_guard'],
            // Permissions catalog
            'manage_crm_permission_guard' => ['manage_permissions_guard'],
            'create_crm_permission_guard' => ['manage_permissions_guard'],
            'edit_crm_permission_guard' => ['manage_permissions_guard'],
            'manage_permissions_guard' => ['manage_crm_permission_guard'],
            // Audit logs — Goal managers with user/role admin may open audit
            'view_crm_activity_log_guard' => ['manage_users_guard', 'manage_roles_guard'],
        ];

        return $map[$permission] ?? [];
    }

    /**
     * Check if user has a specific CRM permission.
     */
    public function hasCrmPermission($permission)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $permission = (string) $permission;

        if (Permission::isWebFormsGuard($permission) && !Company::current()->isWebFormsEnabled()) {
            return false;
        }

        if (Permission::isSocialAdsGuard($permission) && !Company::current()->isSocialAdsEnabled()) {
            return false;
        }

        $rights = json_decode($this->crm_page_right ?? '[]', true);
        if (!is_array($rights)) {
            return false;
        }
        if (in_array('all', $rights, true)) {
            return true;
        }

        $candidates = array_merge([$permission], self::crmPermissionEquivalents($permission));
        foreach ($candidates as $guard) {
            if (in_array($guard, $rights, true)) {
                return true;
            }
        }

        return false;
    }

    public function isSuperAdmin(): bool
    {
        return (int) ($this->crm_role_type ?? -1) === 0;
    }

    /**
     * Count non-super-admin users for Access Control user limit checks.
     */
    public static function countNonSuperAdmins(): int
    {
        return static::whereNotIn('crm_role_type', [0, '0'])->count();
    }

    /**
     * Quotation / CRM email send: Super Admin always; others need Access Control flag + permission.
     */
    public function canSendQuotationEmail(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!Company::current()->isEmailSendEnabled()) {
            return false;
        }

        return $this->hasCrmPermission('send_email_crm_quotations_guard');
    }

    /**
     * Quotation WhatsApp send: Super Admin always; others need Access Control flag + permission.
     */
    public function canSendQuotationWhatsApp(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!Company::current()->isWhatsappSendEnabled()) {
            return false;
        }

        return $this->hasCrmPermission('send_whatsapp_crm_quotations_guard');
    }

    //user name accessor to return name in capitalized format
    public function getUserName()
    {        
        return ucwords($this->name);
    }
}
