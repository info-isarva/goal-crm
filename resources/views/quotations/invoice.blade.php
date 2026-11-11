<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Quotation</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .quotation-wrapper {
            /* max-width: 850px; */
            margin: auto;
            background: #ffffff;
            /* box-shadow: 0 0 10px rgba(0, 0, 0, 0.08); */
        }

        /* HEADER BAR */
        .header {
            display: flex;
            border: 2px solid #d8891c;
        }

        .top-bar {
            background: #d8891c;
            color: #fff;
            padding: 18px 25px;
            font-size: 28px;
            font-weight: bold;
            width: 50%;
        }

        .logo {
            width: 50%;
            text-align: right;
            padding: 10px 25px;
        }

        .content {
            padding: 25px;
        }

        /* COMPANY + QUOTE INFO */
        .company-section {
            display: flex;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .left-box {
            width: 70%;
        }

        .right-box {
            width: 30%;
            text-align: right;
        }

        .right-box {
            width: 30%;
        }

        .quote-info-table {
            width: 100%;
            background: #f2f2f2;
            padding: 10px;
            border-collapse: collapse;
            font-size: 13px;
        }

        .quote-info-table td {
            padding: 4px 8px;
        }

        .quote-info-table .label {
            color: #555;
        }

        .quote-info-table .value {
            text-align: right;
            font-weight: bold;
        }

        .bill-title {
            color: #d8891c;
            font-weight: bold;
            margin-bottom: 6px;
            display: block;
        }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .items-table th {
            background: #d8891c;
            color: #fff;
            padding: 10px;
            text-align: left;
        }

        .items-table td {
            border: 1px solid #e5e5e5;
            padding: 8px;
        }

        .items-table tr:nth-child(even) {
            background: #f7f7f7;
        }

        /* TOTALS */
        .totals-section {
            width: 320px;
            margin-left: auto;
            margin-top: 25px;
            border-collapse: collapse;
        }

        .totals-section td {
            padding: 8px;
        }

        .totals-section tr {
            border-bottom: 1px solid #e5e5e5;
        }

        .totals-section tr:last-child {
            background: #d8891c;
            color: #fff;
            font-weight: bold;
            border: none;
        }

        /* TERMS */
        .bottom-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            font-size: 13px;
        }

        .terms-box {
            width: 65%;
        }

        .approved-box {
            width: 30%;
            text-align: right;
        }

        .section-title {
            color: #d8891c;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .footer-note {
            margin-top: 15px;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="quotation-wrapper"> <!-- HEADER -->
        <div class="header">
            <div class="top-bar"> Quotation </div>
            <div class="logo">
                <img src="{{ public_path('assets/company_image/' . $company->logo) }}"
     width="120"
     height="45">
            </div>
        </div>
        <div class="content"> <!-- COMPANY + QUOTE INFO -->
            <div class="company-section">
                <div class="left-box"> <strong>{{ $company->name }}</strong><br> {{ $company->address }}<br>
                    {{ $company->city }}, {{ $company->state }}<br> {{ $company->country }} -
                    {{ $company->pincode }}<br> {{ $company->phone }}<br> {{ $company->email }}<br> </div>
                <div class="right-box">
                    <table class="quote-info-table">
                        <tr>
                            <td class="label">Date:</td>
                            <td class="value">{{ $quotation->created_at->format('F d, Y') }}</td>
                        </tr>
                        <tr>
                            <td class="label">Quote #</td>
                            <td class="value">{{ $quotation->quote_no }}</td>
                        </tr>
                        <tr>
                            <td class="label">Valid Until:</td>
                            <td class="value">
                                {{ $quotation->valid_until ? \Carbon\Carbon::parse($quotation->valid_until)->format('d M Y') : '-' }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="company-section">
                <div class="left-box"> <span class="bill-title">Billing To:</span>
                    <strong>{{ $quotation->organization->name }}</strong><br>
                    {{ $quotation->addresses->where('address_type', 'billing')->first()->flat_no ?? '-' }}<br>
                    {{ $quotation->addresses->where('address_type', 'billing')->first()->address_line1 ?? '-' }},<br />
                    {{ $quotation->addresses->where('address_type', 'billing')->first()->city ?? '-' }},
                    {{ $quotation->addresses->where('address_type', 'billing')->first()->state ?? '-' }}<br>
                    {{ $quotation->addresses->where('address_type', 'billing')->first()->country ?? '-' }}<br />
                    {{ $quotation->addresses->where('address_type', 'billing')->first()->postal_code ?? '-' }}<br>
                </div>
                <div class="right-box"> <span class="bill-title">Shipping To:</span>
                    <strong>{{ $quotation->organization->name }}</strong><br>
                    {{ $quotation->addresses->where('address_type', 'shipping')->first()->flat_no ?? '-' }}<br>
                    {{ $quotation->addresses->where('address_type', 'shipping')->first()->address_line1 ?? '-' }},<br />
                    {{ $quotation->addresses->where('address_type', 'shipping')->first()->city ?? '-' }},
                    {{ $quotation->addresses->where('address_type', 'shipping')->first()->state ?? '-' }}<br>
                    {{ $quotation->addresses->where('address_type', 'shipping')->first()->country ?? '-' }}<br />
                    {{ $quotation->addresses->where('address_type', 'shipping')->first()->postal_code ?? '-' }}<br>
                </div>
            </div> <!-- ITEMS TABLE -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width:35%">Name</th>
                        <th style="width:10%">Quantity</th>
                        <th style="width:15%">Price</th>
                        <th style="width:10%">Discount</th>
                        <th style="width:10%">Tax</th>
                        <th style="width:20%">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quotation->items as $item)
                        <tr>
                            <td>{{ $item->product->product_name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $company->currency_symbol }} {{ number_format($item->price, 2) }}</td>
                            <td>{{ number_format($item->discount, 2)}}</td>
                            <td>{{ $item->tax }}%</td>
                            <td>{{ $company->currency_symbol }} {{ number_format($item->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table> <!-- TOTALS -->
            <table class="totals-section">
                <tr>
                    <td>Sub Total</td>
                    <td align="right">{{ $company->currency_symbol }} {{ number_format($quotation->subtotal, 2) }}
                    </td>
                </tr>
                <tr>
                    <td>Sales Tax (%)</td>
                    <td align="right"> {{ number_format($quotation->overall_tax, 0) }} </td>
                </tr>
                <tr>
                    <td>Discount</td>
                    <td align="right"> {{ $company->currency_symbol }}
                        {{ number_format($quotation->discount ?? 0, 2) }} </td>
                </tr>
                <tr>
                    <td>Grand Total</td>
                    <td align="right"> {{ $company->currency_symbol }} {{ number_format($quotation->grand_total, 2) }}
                    </td>
                </tr>
            </table> <!-- TERMS + APPROVAL -->
            <div class="bottom-section">
                <div class="terms-box">
                    <div class="section-title">Terms & Conditions</div> {{ $quotation->terms_conditions }}

                </div>
                <div class="approved-box">
                    <div class="section-title">Approved By</div> {{ $quotation->approved_by }}
                </div>
            </div>
        </div>
    </div>
</body>

</html>
