<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .code {
            font-size: 24px;
            font-weight: bold;
            color: #4F46E5;
            letter-spacing: 5px;
            text-align: center;
            margin: 20px 0;
        }

        .footer {
            font-size: 12px;
            color: #777;
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>Welcome to RunConnect!</h2>
        </div>
        <p>Hello,</p>
        <p>Please use the verification code below to complete your registration:</p>

        <div class="code">{{ $code }}</div>

        <p>This code will expire in 3 minutes.</p>

        <div class="footer">
            If you did not request this code, please ignore this email.
        </div>
    </div>
</body>

</html>