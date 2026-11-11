<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_no',
        'subject',
        'quotation_title',
        'quotation_product_type',
        'currency_code',
        'currency_symbol',
        'organization_id',
        'people_id',
        'deal_id',
        'proposal_version',
        'valid_until',
        'status',
        'owner_id',
        'description',
        'terms_conditions',
        'project_detail',
        'show_bank_details',
        'quotation_template_id',
        'quotation_template_version_id',
        'template_html_snapshot',
        'template_css_snapshot',
        'template_version_number',
        'subtotal',
        'discount',
        'discount_percent',
        'tax',
        'adjustment',
        'grand_total',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'show_bank_details' => 'boolean',
        'valid_until' => 'date',
        'subtotal' => 'float',
        'discount' => 'float',
        'discount_percent' => 'float',
        'tax' => 'float',
        'adjustment' => 'float',
        'grand_total' => 'float',
        'proposal_version' => 'integer',
    ];

    public function people()
    {
        return $this->belongsTo(Person::class);
    }

    public function quotationTemplate()
    {
        return $this->belongsTo(QuotationTemplate::class, 'quotation_template_id');
    }

    public function quotationTemplateVersion()
    {
        return $this->belongsTo(QuotationTemplateVersion::class, 'quotation_template_version_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function addresses()
    {
        return $this->hasMany(QuotationAddress::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function whatsappSendLogs()
    {
        return $this->hasMany(QuotationWhatsappSendLog::class)->latest();
    }

    public function emailSendLogs()
    {
        return $this->hasMany(QuotationEmailSendLog::class)->latest();
    }
}
