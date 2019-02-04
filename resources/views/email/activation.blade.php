<!DOCTYPE html>
<html>
<head>
    <title>User : Email Verification</title>
</head>
<body>
<p>Dear <strong>{{ $user['full_name'] }},</strong></p>
<p><a href="{{ $user['activationLink'] }}">Click Here</a> to Verify Your Email Address. This Link is valid for 48 Hours.</p>
</body>
</html>