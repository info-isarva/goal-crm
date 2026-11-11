<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Quotation</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica', sans-serif;
        }

        .quotation-wrapper {
            width: 100%;
            margin: auto;
            border: 1px solid #ccc;
            background: #fff;
            /* padding: 15px; */
        }

        .header {
            text-align: center;
            padding: 10px 0;
            border-bottom: 2px solid #333;
        }

        .header .top-bar {
            font-size: 26px;
            font-weight: bold;
            color: #333;
        }

        .header .logo img {
            max-height: 60px;
            margin-top: 10px;
        }

        .content {
            margin-top: 20px;
        }

        .company-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .left-box, .right-box {
            display: table-cell;
            vertical-align: top;
        }

        .left-box {
            width: 65%;
        }

        .right-box {
            width: 35%;
            text-align: right;
        }

        .items-table, .totals-section {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .items-table th, .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .items-table th {
            background: #333;
            color: #fff;
        }

        .totals-section td {
            padding: 8px;
            text-align: right;
        }

        .totals-section tr:last-child {
            background: #333;
            color: #fff;
            font-weight: bold;
        }

        .footer-note {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>

<div class="quotation-wrapper">

    <div class="header">
        <div class="top-bar">
            Quotation
        </div>
        <div class="logo">

        </div>
    </div>

    <div class="content">

        <div class="company-section">
            <div class="left-box">
                <strong>{{ $company->name }}</strong><br>
                {{ $company->address }}<br>
                {{ $company->city }}, {{ $company->state }}<br>
                {{ $company->country }} - {{ $company->pincode }}<br>
                {{ $company->phone }}<br>
                {{ $company->email }}<br>
            </div>

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
                        <td class="value">{{ $quotation->valid_until ? $quotation->valid_until : '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="company-section">
            <div class="left-box">
                <span class="bill-title">Billing To:</span>
                <strong>{{ $quotation->organization->name }}</strong><br>
                {{ $quotation->addresses->where('address_type', 'billing')->first()->flat_no ?? '-' }}<br>
                {{ $quotation->addresses->where('address_type', 'billing')->first()->address_line1 ?? '-' }},<br/>
                {{ $quotation->addresses->where('address_type', 'billing')->first()->city ?? '-' }},
                {{ $quotation->addresses->where('address_type', 'billing')->first()->state ?? '-' }}<br>
                {{ $quotation->addresses->where('address_type', 'billing')->first()->country?? '-' }}<br/>
                {{ $quotation->addresses->where('address_type', 'billing')->first()->postal_code ?? '-' }}<br>

            </div>

            <div class="right-box">
                <span class="bill-title">Shipping To:</span>
                <strong>{{ $quotation->organization->name }}</strong><br>
                {{ $quotation->addresses->where('address_type', 'shipping')->first()->flat_no ?? '-' }}<br>
                {{ $quotation->addresses->where('address_type', 'shipping')->first()->address_line1 ?? '-' }},<br/>
                {{ $quotation->addresses->where('address_type', 'shipping')->first()->city ?? '-' }},
                {{ $quotation->addresses->where('address_type', 'shipping')->first()->state ?? '-' }}<br>
                {{ $quotation->addresses->where('address_type', 'shipping')->first()->country?? '-' }}<br/>
                {{ $quotation->addresses->where('address_type', 'shipping')->first()->postal_code ?? '-' }}<br>

            </div>
        </div>


        <table class="items-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Unit</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotation->items as $item)
                <tr>
                    <td>{{ $item->product->product_name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $company->currency_symbol }} {{ number_format($item->price, 2) }}</td>
                    <td>month</td>
                    <td>{{ $company->currency_symbol }} {{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals-section">
            <tr>
                <td>Sub Total</td>
                <td align="right">{{ $company->currency_symbol }} {{ number_format($quotation->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td>Sales Tax (%)</td>
                <td align="right">{{ number_format($quotation->overall_tax, 0) }}</td>
            </tr>
            <tr>
                <td>Discount</td>
                <td align="right">{{ $company->currency_symbol }} {{ number_format($quotation->discount ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td>Grand Total</td>
                <td align="right">{{ $company->currency_symbol }} {{ number_format($quotation->grand_total, 2) }}</td>
            </tr>
        </table>

        <div class="bottom-section">
            <div class="terms-box">
                <div class="section-title">Terms & Conditions</div>
                {{ $quotation->terms_conditions }}
                <div class="footer-note">Copyright © Isarva Infotech Pvt Ltd, All Rights Reserved</div>
            </div>

            <div class="approved-box">
                <div class="section-title">Approved By</div>
                {{ $quotation->approved_by }}
            </div>
        </div>

    </div>
</div>

</body>
</html>
