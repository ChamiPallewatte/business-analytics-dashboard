<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333333;
            margin: 0;
            padding: 0;
            font-size: 13px;
            line-height: 1.4;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 10px;
        }
        .header {
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5; /* Premium Indigo */
            text-transform: uppercase;
        }
        .document-title {
            font-size: 28px;
            font-weight: 300;
            text-align: right;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .company-details {
            font-size: 11px;
            color: #4b5563;
            line-height: 1.5;
        }
        .meta-details {
            text-align: right;
            font-size: 12px;
            color: #1f2937;
            line-height: 1.5;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .details-table td {
            width: 50%;
            vertical-align: top;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 4px;
        }
        .info-block {
            line-height: 1.5;
        }
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .item-table th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: bold;
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            border-bottom: 2px solid #e5e7eb;
        }
        .item-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        .item-desc {
            font-weight: bold;
            color: #111827;
        }
        .item-subdesc {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }
        .text-right {
            text-align: right !important;
        }
        .summary-table {
            width: 40%;
            float: right;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .summary-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #f3f4f6;
        }
        .summary-table tr.total {
            font-weight: bold;
            font-size: 14px;
            color: #4f46e5;
            background-color: #f8fafc;
            border-top: 2px solid #e5e7eb;
        }
        .summary-table tr.total td {
            border-bottom: 2px solid #e5e7eb;
        }
        .remarks-section {
            width: 55%;
            float: left;
            font-size: 11px;
            color: #4b5563;
            line-height: 1.5;
        }
        .clearfix {
            clear: both;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 10px;
            font-weight: bold;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .badge-paid { background-color: #d1fae5; color: #065f46; }
        .badge-partially_paid { background-color: #fef3c7; color: #92400e; }
        .badge-sent { background-color: #dbeafe; color: #1e40af; }
        .badge-overdue { background-color: #fee2e2; color: #991b1b; }
        .badge-draft { background-color: #f3f4f6; color: #374151; }
        .badge-cancelled { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

    <div class="invoice-box">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td>
                        <div class="company-name">{{ $companyName }}</div>
                        <div class="company-details" style="margin-top: 5px;">
                            {!! nl2br(e($companyAddress)) !!}<br>
                            @if($companyTrn)
                                <strong>TRN:</strong> {{ $companyTrn }}
                            @endif
                        </div>
                    </td>
                    <td class="meta-details">
                        <div class="document-title">Invoice</div>
                        <div style="margin-top: 10px;">
                            <strong>Invoice #:</strong> {{ $invoice->invoice_number }}<br>
                            <strong>Date:</strong> {{ $invoice->invoice_date->format('d M Y') }}<br>
                            <strong>Due Date:</strong> {{ $invoice->due_date->format('d M Y') }}<br>
                            <strong>Status:</strong> 
                            <span class="badge badge-{{ strtolower(str_replace(' ', '_', $invoice->status)) }}">
                                {{ $invoice->status }}
                            </span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="details-table">
            <tr>
                <td>
                    <div class="section-title">Billed To</div>
                    <div class="info-block">
                        <strong>{{ $invoice->client->name }}</strong><br>
                        @if($invoice->client->company_name)
                            {{ $invoice->client->company_name }}<br>
                        @endif
                        @if($invoice->client->address)
                            {!! nl2br(e($invoice->client->address)) !!}<br>
                        @endif
                        @if($invoice->client->trn)
                            <strong>TRN:</strong> {{ $invoice->client->trn }}<br>
                        @endif
                        <strong>Email:</strong> {{ $invoice->client->email }}<br>
                        <strong>Phone:</strong> {{ $invoice->client->mobile }}
                    </div>
                </td>
                <td style="padding-left: 40px;">
                    <div class="section-title">Payment Information</div>
                    <div class="info-block">
                        <strong>Payment Terms:</strong> {{ $invoice->payment_terms }}<br>
                        @if($invoice->service)
                            <strong>Service Type:</strong> {{ $invoice->service->type }}<br>
                            <strong>Billing Interval:</strong> {{ $invoice->service->billing_interval }}<br>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <table class="item-table">
            <thead>
                <tr>
                    <th style="width: 70%;">Description</th>
                    <th class="text-right" style="width: 30%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="item-desc">
                            @if($invoice->service)
                                Service: {{ $invoice->service->type }}
                            @else
                                Professional Services / Consulting
                            @endif
                        </div>
                        <div class="item-subdesc">
                            Billing period or milestones as agreed.
                            @if($invoice->remarks)
                                <div style="margin-top: 5px; font-style: italic;">Note: {{ $invoice->remarks }}</div>
                            @endif
                        </div>
                    </td>
                    <td class="text-right font-bold" style="vertical-align: middle;">
                        ${{ number_format($invoice->amount, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="remarks-section">
            <div class="section-title">Terms & Notes</div>
            <div style="line-height: 1.6;">
                1. Please pay within the due date to avoid service disruption.<br>
                2. Payments can be made via Bank Transfer, Cheque, or Online payment gateway.<br>
                3. For any billing inquiries, contact billing@aiwa.agency.
            </div>
        </div>

        <table class="summary-table">
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">${{ number_format($invoice->amount, 2) }}</td>
            </tr>
            <tr>
                <td>VAT ({{ number_format($invoice->vat_percent, 2) }}%):</td>
                <td class="text-right">${{ number_format($invoice->vat_amount, 2) }}</td>
            </tr>
            <tr class="total">
                <td>Total:</td>
                <td class="text-right">${{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </table>

        <div class="clearfix"></div>
    </div>

    <div class="footer">
        Thank you for your business! &copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.
    </div>

</body>
</html>
