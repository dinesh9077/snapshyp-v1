<!DOCTYPE html>
<html>
<head>
    <title>Your OTP Code</title>
</head>
<body>
    <p>Dear {{ $username }},</p>
    
    <p>Your One-Time Password (OTP) for account verification is:</p>
    
    <h2 style="color:blue;">{{ $otp }}</h2>

    <p>Please use this OTP to complete your registration.</p>

    <p>If you did not request this OTP, please ignore this email.</p>

    <p>Thank you,</p>
    <p><strong>{{ config('app.name') }}</strong></p>
</body>
</html>
