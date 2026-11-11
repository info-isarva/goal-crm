<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationTemplateVersion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'quotation_template_id',
        'version',
        'html_content',
        'css_content',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'version' => 'integer',
        'created_at' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(QuotationTemplate::class, 'quotation_template_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
