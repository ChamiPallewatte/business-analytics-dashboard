<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Service Renewal Reminder</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
            color: #1f2937;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .header {
            background-color: #4f46e5; /* Premium Indigo */
            padding: 30px 40px;
            color: #ffffff;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .content {
            padding: 40px;
            line-height: 1.6;
        }
        .content p {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 15px;
        }
        .details-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
        }
        .details-table td {
            padding: 8px 0;
            font-size: 14px;
        }
        .details-table td.label {
            font-weight: bold;
            color: #4b5563;
            width: 40%;
        }
        .details-table td.value {
            color: #1f2937;
            text-align: right;
        }
        .btn {
            display: block;
            width: 200px;
            margin: 0 auto 30px auto;
            text-align: center;
            background-color: #4f46e5;
            color: #ffffff !important;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-size: 15px;
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);
        }
        .btn:hover {
            background-color: #4338ca;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px 40px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h1>Renewal Reminder</h1>
        </div>
        <div class="content">
            <p>Dear {{ $clientName }},</p>
            <p>This is a friendly reminder that your active service with <strong>{{ $companyName }}</strong> is due for renewal soon. Please review the details below:</p>
            
            <div class="details-box">
                <table class="details-table">
                    <tr>
                        <td class="label">Service:</td>
                        <td class="value">{{ $serviceType }}</td>
                    </tr>
                    <tr>
                        <td class="label">Renewal Date:</td>
                        <td class="value"><strong>{{ $renewalDate }}</strong> (In {{ $daysRemaining }} days)</td>
                    </tr>
                    <tr>
                        <td class="label">Annual Cost:</td>
                        <td class="value">${{ number_format($totalAmount, 2) }}</td>
                    </tr>
                </table>
            </div>

            <p>To ensure uninterrupted service, please arrange for renewal payment or contact your Account Manager if you have any questions.</p>
            
            <a href="{{ $loginUrl }}" class="btn">View Dashboard</a>

            <p style="margin-bottom: 0;">Best regards,<br><strong>{{ $companyName }} Team</strong></p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.<br>
            If you did not expect this email, please ignore it.
        </div>
    </div>

</body>
</html>
