<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Request Rejection Notification</title>
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
            color: #336AEA;
            font-weight: 700;
        }
        .highlight {
            color: #41aef1;
        }
        .message-text {
            text-align: center;
            font-size: 18px;
            margin-bottom: 20px;
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
                Swap<span class="highlight">2Naira</span>
            </h1>
        </div>
        <h2>Hello Admin,</h2>
        <p class="message-text">
            This is to notify you that the withdrawal request from user <strong>{{ $name }}</strong> off <strong>{{ $amount }}</strong> has been rejected.
        </p>
        <p class="message-text">
            The user as been emailed with the reason for the rejection. Provide any necessary support or further instructions.
        </p>
        <p class="signature">
            Best Regards,<br>
            Swap2Naira Team
        </p>
    </div>
</body>
</html>
