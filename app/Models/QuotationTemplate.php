<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuotationTemplate extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'html_content',
        'css_content',
        'status',
        'is_default',
        'version',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'version' => 'integer',
        'deleted_at' => 'datetime',
    ];

    public function versions()
    {
        return $this->hasMany(QuotationTemplateVersion::class)->orderByDesc('version');
    }

    public function latestVersion()
    {
        return $this->hasOne(QuotationTemplateVersion::class)->latestOfMany('version');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class, 'quotation_template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isInUse(): bool
    {
        return $this->quotations()->exists();
    }
}
