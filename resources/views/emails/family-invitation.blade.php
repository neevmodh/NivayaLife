<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>You're invited to NivayaLife</title>
</head>
<body style="margin:0; padding:0; background-color:#FBF6EA; font-family: -apple-system, 'Helvetica Neue', Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FBF6EA; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px; background-color:#ffffff; border-radius:24px; overflow:hidden; box-shadow: 0 12px 32px -12px rgba(30,90,69,0.18);">

                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#14503F; padding:32px 40px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <span style="font-size:20px; font-weight:800; color:#ffffff; letter-spacing:-0.02em;">NivayaLife</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:40px;">
                            <p style="margin:0 0 4px; font-size:13px; font-weight:700; color:#14503F; text-transform:uppercase; letter-spacing:0.06em;">Family invitation</p>
                            <h1 style="margin:0 0 16px; font-size:24px; font-weight:800; color:#1F2A24; line-height:1.3;">
                                {{ $inviterName }} added you as their {{ str_replace('_', ' ', $relation) }} on NivayaLife
                            </h1>
                            <p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#1F2A24;">
                                NivayaLife is a secure, family-oriented health record manager. {{ $inviterName }} has started a profile
                                for you and invited you to claim it with your own login — you'll decide exactly what they can
                                see once you're in.
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                                <tr>
                                    <td style="border-radius:14px; background-color:#14503F;">
                                        <a href="{{ $acceptUrl }}" target="_blank"
                                            style="display:inline-block; padding:14px 32px; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:14px;">
                                            Accept invitation
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px; font-size:13px; color:#6B7A72; text-align:center;">
                                Or copy and paste this link into your browser:
                            </p>
                            <p style="margin:0 0 24px; font-size:13px; color:#14503F; text-align:center; word-break:break-all;">
                                {{ $acceptUrl }}
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FBF6EA; border-radius:14px; margin-top:8px;">
                                <tr>
                                    <td style="padding:16px 20px; font-size:13px; color:#6B7A72;">
                                        This invitation expires on <strong style="color:#1F2A24;">{{ $expiresAt->format('F j, Y \a\t g:i A') }}</strong>.
                                        If you weren't expecting this, you can safely ignore this email.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:24px 40px; border-top:1px solid #EFEAE0;">
                            <p style="margin:0; font-size:12px; color:#9AA79F; text-align:center;">
                                &copy; {{ date('Y') }} NivayaLife &middot; Your family's health records, organized and secure.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
