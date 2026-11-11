<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    protected $table = 'user_details';
    protected $fillable = [
        'user_id', 'address', 'mobile', 'city', 'state', 'country', 'created_by', 'updated_by', 'deleted_by'
    ];
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
