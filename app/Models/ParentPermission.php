<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentPermission extends Model
{
    protected $table = 'parent_permissions';
    protected $fillable = [
        'name'
    ];

    public function permissions()
    {
        return $this->hasMany(Permission::class, 'parent_id');
    }
}
