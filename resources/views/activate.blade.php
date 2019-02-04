<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="utf-8">
    <!-- utf-8 works for most cases -->
    <meta name="viewport" content="width=device-width">
    <!-- Forcing initial-scale shouldn't be necessary -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Use the latest (edge) version of IE rendering engine -->
    <meta name="x-apple-disable-message-reformatting">
    <!-- Disable auto-scale in iOS 10 Mail entirely -->
    <title>VideoBook  -  Email Acivation</title>
    <!-- The title tag shows in email notifications, like Android 4.4. -->
    <!-- CSS Reset -->
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,700" rel="stylesheet">
    <style>

        html,
        body {
            margin: 0 auto !important;
            padding: 0 !important;
            height: 100vh !important;
        }

        /* What it does: Stops email clients resizing small text. */

        * {
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }

        /* What it does: Centers email on Android 4.4 */

        div[style*="margin: 16px 0"] {
            margin: 0 !important;
        }

        /* What it does: Stops Outlook from adding extra spacing to tables. */

        table,
        td {
            mso-table-lspace: 0pt !important;
            mso-table-rspace: 0pt !important;
        }

        /* What it does: Fixes webkit padding issue. Fix for Yahoo mail table alignment bug. Applies table-layout to the first 2 tables then removes for anything nested deeper. */

        table {
            border-spacing: 0 !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            margin: 0 auto !important;
        }

        table table table {
            table-layout: auto;
        }

        /* What it does: Uses a better rendering method when resizing images in IE. */

        img {
            -ms-interpolation-mode: bicubic;
        }

        /* What it does: A work-around for email clients meddling in triggered links. */

        *[x-apple-data-detectors],
            /* iOS */

        .x-gmail-data-detectors,
            /* Gmail */

        .x-gmail-data-detectors *,
        .aBn {
            border-bottom: 0 !important;
            cursor: default !important;
            color: inherit !important;
            text-decoration: none !important;
            font-size: inherit !important;
            font-family: inherit !important;
            font-weight: inherit !important;
            line-height: inherit !important;
        }

        @media (max-width:600px){
            .main-container{
                width:100% !important;
            }
        }

    </style>
</head>

<body style="width: 100%;margin: 0; background-color: #dddddd;height: 100%;">
<table cellspacing="0" cellpadding="0" border="0" width="600px" height="100%" class="main-container" >
    <tbody style="display: table-cell;height: 100%;vertical-align: middle;">
    <!--header-->
    <tr style="-webkit-box-shadow: 0px -1px 9px -3px rgba(124,124,124,1);-moz-box-shadow: 0px -1px 9px -3px rgba(124,124,124,1);box-shadow: 0px -1px 9px -3px rgba(124,124,124,1);border-top: 4px solid #2f6fc5;">
        <td align="center" valign="middle" style="background-color: #ffffff; height:150px; width:100%;border-bottom: 1px solid #eae9e9;">
            <a href="#" title="Video Series Logo"><img src="{{ asset('admin/images/logo.png') }}" alt="Video Series Logo" style="max-width: 100%;width:155px;height:130px"></a>
        </td>
    </tr>
    <!--header-->
    <!--content-->
    <tr style="-webkit-box-shadow: 0px 1px 9px -3px rgba(124,124,124,1);-moz-box-shadow: 0px 1px 9px -3px rgba(124,124,124,1);box-shadow: 0px 1px 9px -3px rgba(124,124,124,1);">
        <td align="top" valign="middle">
            <table cellspacing="0" border="0" width="100%" height="100%" border="0">
                <tbody>
                <tr>
                    <td align="top" valign="top" colspan="2" style="background: #eeeeee;border: 1px solid #eeeeee; padding:20px 25px 0px; font-size: 15px;font-family: 'Poppins', sans-serif;color:#2a2a2a;line-height: 1.4;">
                        @if($success == 0)
                            <p style="font-size: 22px;margin: 0 0px;color: #000;text-align: center">
                                <img src="{{ asset('images/cancel.png') }}" alt="Success!" style="width: 100px;">
                            </p>
                            <p style="font-size: 22px;margin: 0 0px;color: #000;text-align: center">Failed!</p>
                        @else
                            <p style="font-size: 22px;margin: 0 0px;color: #000;text-align: center">
                                <img src="{{ asset('images/checked.png') }}" alt="Success!" style="width: 100px;">
                            </p>
                            <p style="font-size: 22px;margin: 0 0px;color: #000;text-align: center">Success!</p>
                        @endif
                        <p style="color:#2a2a2a;font-weight: 700;margin-top: 10px;font-size: 17px;">Hey {{$name}} ,</p>
                        <p style="font-family: 'Poppins', sans-serif;color:#2a2a2a;">{{ $message }}</p>
                    </td>
                </tr>
                <tr>
                    <td align="top" valign="top" colspan="2" style="background: #eeeeee; padding:0px 25px 10px; font-size: 15px;font-family: 'Poppins', sans-serif;color:#2a2a2a;line-height: 1.4;">
                        <p style="margin-bottom: 0;">Thank you,</p>
                        <p style="margin-top: 0;">{{trans('admin-labels.APP_NAME')}} Team</p>
                    </td>
                </tr>
                <tr>
                    <td align="top" valign="top" style="background: #eeeeee; padding:20px 25px 10px; font-size: 15px;font-family: 'Poppins', sans-serif;color:#9a9a9a;line-height: 1.4;border-top: 2px solid #e5e5e5;border-right: 2px solid #e5e5e5;">
                        <p style="margin-top: 0;font-size: 16px;color: #000;margin-bottom: 5px;">Your Account</p>
                        <p style="font-size: 15px;color: #2a2a2a;margin-top: 0;"><a href="#" style="color: #2f6fc5;font-weight: 700;text-decoration: none;">View details</a> about your account.</p>
                    </td>
                    <td align="top" valign="top" style="background: #eeeeee; padding:20px 25px 10px; font-size: 15px;font-family: 'Poppins', sans-serif;color:#9a9a9a;line-height: 1.4;border-top: 2px solid #e5e5e5;">
                        <p style="margin-top: 0;font-size: 16px;color: #000;margin-bottom: 5px;">Need help?</p>
                        <p style="font-size: 15px;color: #2a2a2a;margin-top: 0;">Contact <a href="#" style="color: #2f6fc5;font-weight: 700;text-decoration: none;">Customer Support</a> for assistance with your account.</p>
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <!--content end-->
    </tbody>
</table>
</body>

</html>
