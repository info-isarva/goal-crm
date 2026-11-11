<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use SoftDeletes;

    protected $table = 'permissions';

    protected $fillable = [
        'parent_id',       
        'name',
        'guard_name',
        'description',
        'crm_permission',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function parentPermission()
    {
        return $this->belongsTo(\App\Models\ParentPermission::class, 'parent_id');
    }

    public static function webFormsGuards(): array
    {
        return [
            'manage_crm_web_forms_guard',
            'view_crm_web_forms_guard',
            'create_crm_web_forms_guard',
            'edit_crm_web_forms_guard',
            'delete_crm_web_forms_guard',
        ];
    }

    public static function socialAdsGuards(): array
    {
        return [
            'manage_crm_social_ads_guard',
            'view_crm_social_ads_guard',
            'create_crm_social_ads_guard',
            'edit_crm_social_ads_guard',
            'delete_crm_social_ads_guard',
        ];
    }

    public static function isWebFormsGuard(string $guard): bool
    {
        return in_array($guard, static::webFormsGuards(), true) || str_contains($guard, '_web_forms_');
    }

    public static function isSocialAdsGuard(string $guard): bool
    {
        return in_array($guard, static::socialAdsGuards(), true) || str_contains($guard, '_social_ads_');
    }
}
