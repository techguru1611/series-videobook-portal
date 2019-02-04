<!DOCTYPE html>
<html>
<head>
    <title>User : Email Verification</title>
</head>
<body>
<p>Dear <strong>{{ $user['full_name'] }},</strong></p>
<p>Your Activation OTP is here : {{ $user['activationOTP'] }} . This OTP is valid for 48 Hours.</p>
</body>
</html>