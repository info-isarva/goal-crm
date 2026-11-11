<!DOCTYPE html>
<html>
    
<head>
<meta charset="UTF-8">

<style>
    body {
        font-family: dejavusans;
        font-size: 12px;
        margin: 0;
        color: #333333;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    .section-space {
        margin-top: 22px;
    }

    /* ===== HEADER ===== */
    .header-left {
        background-color:#2f7f36;
        color:#ffffff;
        font-size:22px;
        font-weight:bold;
        padding:15px;
    }

    .header-right {
        border-top:2px solid #2f7f36;
        border-right:2px solid #2f7f36;
        border-bottom:2px solid #2f7f36;
        padding:12px;
    }

    .title-center {
    text-align: center;
    font-size: 22px;
    font-weight: bold;
    color: #2f7f36;
}

    /* ===== TITLES ===== */
    .section-title {
        color: #2f7f36;
        font-weight: bold;
        font-size: 13px;
    }

    .bill-title {
        color: #2f7f36;
        font-weight: bold;
        font-size: 13px;
    }

    /* ===== ITEMS TABLE ===== */
    .section-space {
    margin-top: 20px;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}

.items-table th {
    background-color: #2f7f36;
    color: #ffffff;
    padding: 10px 8px;
    border: 1px solid #2f7f36;
    font-weight: bold;
    line-height: 30px;
    vertical-align: middle;
    text-align: left;

}

.items-table td {
    padding: 9px 8px;
    border: 1px solid #eeeeee;
    line-height: 30px;
    vertical-align: middle;
    text-align: left;
}

.text-left {
    text-align: left;
}

.text-center {
    text-align: center;
}

.text-right {
    text-align: right;
}

/* Zebra row */
.items-table tbody tr:nth-child(even) {
    background-color: #f7faf7;
}
    /* ===== TOTALS ===== */
    .totals-wrapper {
        margin-top: 15px;
    }

    .totals-box {
        width: 100%;
        /* border: 1px solid #e6e6e6; */
        border-collapse: collapse;
        font-size: 12px;
    }

    .totals-box td {
        padding: 9px 10px;
        line-height: 30px;
        vertical-align: middle;
        border-top: 1px solid #eeeeee;
    }

    .totals-label {
        text-align: left;
        color: #444444;
    }

    .totals-value {
        text-align: right;
    }

    .grand-row td {
        background-color: #eef7ef;
        color: #23652a;
        font-weight: bold;
        font-size: 14px;
        line-height: 22px;
        border-top: 2px solid #399b43;
    }

</style>

</head>

<body>

<!-- ================= HEADER ================= -->
<!-- <table>
<tr>
    <td width="60%" class="header-left" valign="middle" style="display: table-cell; vertical-align: middle;">
        Quotation
    </td>

    <td width="40%" align="right" class="header-right" valign="middle">
        @if(!empty($company->logo))
            <img src="{{ public_path('assets/company_image/' . $company->logo) }}" height="40">
        @endif
    </td>
</tr>
</table> -->
<div class="title-center">
    QUOTATION
</div>
<br><br/>


<!-- ================= COMPANY INFO + QUOTE INFO ================= -->
<table style="width: 100%; border-collapse: collapse;">
    <tr>
        <!-- LEFT -->
        <td width="50%" valign="top" style="text-align: left; line-height: 1.6;">
            <strong>Quote #:</strong> {{ $quotation->quote_no }}
        </td>

        <!-- RIGHT -->
        <td width="50%" valign="top" style="text-align: right; line-height: 1.6;">
            <strong>Date:</strong> {{ $quotation->created_at->format('d M Y') }}<br>
            <strong>Valid Until:</strong>
            {{ $quotation->valid_until ? \Carbon\Carbon::parse($quotation->valid_until)->format('d M Y') : '-' }}
        </td>
    </tr>
</table>
<br/><br/>

<!-- ================= BILLING / SHIPPING ================= -->
<table class="section-space" style="width: 100%; border-collapse: collapse;">
<tr>
    <td width="50%" valign="top" style="padding-right: 10px; border-right: 1px solid #ddd;">
        <span class="bill-title" style="font-weight: bold;">Billing To</span><br><br>
        <strong>{{ $quotation->organization->name }}</strong><br>
        {{ optional($quotation->addresses->where('address_type','billing')->first())->address_line1 }}<br>
        {{ optional($quotation->addresses->where('address_type','billing')->first())->city }},
        {{ optional($quotation->addresses->where('address_type','billing')->first())->state }}<br>
        {{ optional($quotation->addresses->where('address_type','billing')->first())->country }}
    </td>

    <td width="50%" valign="top" style="padding-left: 10px; text-align: right;">
        <span class="bill-title" style="font-weight: bold;">Shipping To</span><br><br>
        <strong>{{ $quotation->organization->name }}</strong><br>
        {{ optional($quotation->addresses->where('address_type','shipping')->first())->address_line1 }}<br>
        {{ optional($quotation->addresses->where('address_type','shipping')->first())->city }},
        {{ optional($quotation->addresses->where('address_type','shipping')->first())->state }}<br>
        {{ optional($quotation->addresses->where('address_type','shipping')->first())->country }}
    </td>
</tr>
</table>

<br/><br/>

<!-- ================= ITEMS ================= -->
<table class="items-table section-space">

<thead>
<tr>
    <th width="25%" class="text-left">Name</th>
    <th width="10%" class="text-center">Qty</th>
    <th width="20%" class="text-right">Price</th>
    <th width="15%" class="text-right">Discount</th>
    <th width="12%" class="text-center">Tax</th>
    <th width="20%" class="text-right">Total</th>
</tr>
</thead>

<tbody>
@foreach ($quotation->items as $item)
<tr>
    <td width="25%" class="text-left">
        {{ $item->product->product_name }}
    </td>

    <td width="10%" class="text-center">
        {{ $item->quantity }}
    </td>

    <td width="20%" class="text-right">
        {{ $company->currency_symbol }} {{ number_format($item->price,2) }}
    </td>

    <td width="15%" class="text-right">
        {{ number_format($item->discount,2) }}
    </td>

    <td width="12%" class="text-center">
        {{ $item->tax }}%
    </td>

    <td width="20%" class="text-right">
        {{ $company->currency_symbol }} {{ number_format($item->total,2) }}
    </td>
</tr>
@endforeach
</tbody>

</table>
<br/><br/>
<!-- ================= TOTALS ================= -->
<table class="section-space totals-wrapper">
<tr>
    <td width="50%"></td>
    <td width="50%">
        <table class="totals-box">

            <tr>
                <td width="50%" class="totals-label">
                    Sub Total
                </td>
                <td width="50%" class="totals-value">
                    {{ $company->currency_symbol }} {{ number_format($quotation->subtotal,2) }}
                </td>
            </tr>

            <tr>
                <td class="totals-label">
                    Sales Tax
                </td>
                <td class="totals-value">
                    {{ number_format($quotation->overall_tax,0) }}%
                </td>
            </tr>

            <tr>
                <td class="totals-label">
                    Discount
                </td>
                <td class="totals-value">
                    {{ $company->currency_symbol }} {{ number_format($quotation->discount ?? 0,2) }}
                </td>
            </tr>

            <tr class="grand-row">
                <td>
                    Grand Total
                </td>
                <td class="totals-value">
                    {{ $company->currency_symbol }} {{ number_format($quotation->grand_total,2) }}
                </td>
            </tr>

        </table>
    </td>
</tr>
</table>

<br/><br/><br/>
<!-- ================= Description ================= -->
@if(!empty($quotation->description))

<table class="section-space">
<tr>
    <td width="100%" valign="top">
        <span class="section-title">Description</span><br><br>
        {!! nl2br(e($quotation->description)) !!}
    </td>


</tr>
</table>
<br/><br/>
@endif
<!-- ================= TERMS ================= -->
 @if(!empty($quotation->terms_conditions))
<table class="section-space">
<tr>
    <td width="100%" valign="top">
        <span class="section-title">Terms & Conditions</span><br><br>
        {!! nl2br(e($quotation->terms_conditions)) !!}
    </td>


</tr>
</table>

<br/><br/><br/>
@endif
<!-- ================= APPROVAL ================= -->
<table class="section-space">
<tr>
    <td width="50%" valign="top">
        <span class="section-title">Approved By</span><br><br>
        {{ $quotation->approved_by }}
    </td>
</tr>
</table>

</body>
</html>
