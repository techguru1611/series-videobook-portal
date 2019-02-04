<!DOCTYPE html>
<html>
    <body>
        <p>
            Dear {{ $user['full_name'] }},
        </p>
        <p>
            Thank you for registering with us. Your password to login is : <strong>{{ $user['password'] }}</strong>.
        </p>
        <p>
            If you have any queries regarding this email or any issues with login, please notify us by contacting support@seriesvideoportal.com
        </p>
    </body>
</html>
