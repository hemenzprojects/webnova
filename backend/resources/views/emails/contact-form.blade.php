<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Form Submission</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #0A1E3E 0%, #1A3A5C 100%);
            color: white;
            padding: 30px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 0 0 8px 8px;
        }
        .field {
            margin-bottom: 20px;
        }
        .field-label {
            font-weight: bold;
            color: #0A1E3E;
            margin-bottom: 5px;
        }
        .field-value {
            background: white;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .message-box {
            background: white;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #00D9FF;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #777;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>New Contact Form Submission</h1>
        <p style="margin: 5px 0 0 0; opacity: 0.9;">WEBNOVA Website</p>
    </div>

    <div class="content">
        <p>You have received a new contact form submission from your website:</p>

        <div class="field">
            <div class="field-label">Name:</div>
            <div class="field-value">{{ $formData['name'] }}</div>
        </div>

        <div class="field">
            <div class="field-label">Email:</div>
            <div class="field-value">
                <a href="mailto:{{ $formData['email'] }}">{{ $formData['email'] }}</a>
            </div>
        </div>

        @if(!empty($formData['phone']))
        <div class="field">
            <div class="field-label">Phone:</div>
            <div class="field-value">{{ $formData['phone'] }}</div>
        </div>
        @endif

        @if(!empty($formData['company']))
        <div class="field">
            <div class="field-label">Organization:</div>
            <div class="field-value">{{ $formData['company'] }}</div>
        </div>
        @endif

        <div class="message-box">
            <div class="field-label" style="margin-bottom: 10px;">Message:</div>
            <div style="white-space: pre-wrap;">{{ $formData['message'] }}</div>
        </div>

        <div class="footer">
            <p>This email was sent from the WEBNOVA website contact form.</p>
            <p>Submitted on {{ date('F j, Y \a\t g:i A') }}</p>
        </div>
    </div>
</body>
</html>
