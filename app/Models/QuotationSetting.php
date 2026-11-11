<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationSetting extends Model
{
    use HasFactory;

    protected $table = 'quotation_settings';

    protected $fillable = [
        'default_terms_conditions',
    ];

    public static function current(): self
    {
        $setting = static::query()->first();

        if (!$setting) {
            $setting = static::create([
                'default_terms_conditions' => null,
            ]);
        }

        return $setting;
    }

    public static function defaultTermsConditions(): string
    {
        return (string) (static::current()->default_terms_conditions ?? '');
    }
}
