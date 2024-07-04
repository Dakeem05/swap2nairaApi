<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
            color: #333333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .brand {
            color: #1A1D6E;
            font-weight: 700;
        }
        .highlight {
            color: #41aef1;
        }
        .otp-text {
            text-align: center;
            font-size: 18px;
            margin-bottom: 20px;
        }
        .otp-code {
            display: block;
            width: 100%;
            max-width: 200px;
            margin: 0 auto;
            padding: 10px;
            background-color: #1A1D6E;
            color: #ffffff;
            text-align: center;
            border-radius: 4px;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 2px;
        }
        .signature {
            text-align: right;
            margin-top: 20px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="brand">
                Decoding<span class="highlight">TheFuture</span>
            </h1>
        </div>
        <h2>Hello, {{ $name }}!</h2>
        <p class="otp-text">
            You requested to change your password. To complete your action, please use the following OTP code:
        </p>
        <div class="otp-code">{{ $otp }}</div>
        <p class="otp-text">
            If you did not request this code, please ignore this email.
        </p>
        <p class="signature">
            Best Regards,<br>
            Decoding The Future Team
        </p>
    </div>
</body>
</html>
