<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMonthlySales extends Model
{
    protected $table = 'user_monthly_sales';
    protected $fillable = [
        'user_id', 'year', 'month', 'achieved_sales', 'sales_target'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
