<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New commit pending deploy approval</title>
</head>
<body style="margin:0; padding:0; background-color:#FBF6EA; font-family: -apple-system, 'Helvetica Neue', Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FBF6EA; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px; background-color:#ffffff; border-radius:24px; overflow:hidden; box-shadow: 0 12px 32px -12px rgba(30,90,69,0.18);">

                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#1E5A45; padding:32px 40px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <span style="font-size:20px; font-weight:800; color:#ffffff; letter-spacing:-0.02em;">Novix</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:40px;">
                            <p style="margin:0 0 4px; font-size:13px; font-weight:700; color:#1E5A45; text-transform:uppercase; letter-spacing:0.06em;">Deploy approval needed</p>
                            <h1 style="margin:0 0 16px; font-size:24px; font-weight:800; color:#1F2A24; line-height:1.3;">
                                A new commit is waiting to be deployed
                            </h1>
                            <p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#1F2A24;">
                                <strong>{{ $authorName }}</strong> pushed <code style="background:#FBF6EA; padding:2px 6px; border-radius:6px;">{{ $shortSha }}</code> to main:
                            </p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FBF6EA; border-radius:14px; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:16px 20px; font-size:14px; color:#1F2A24;">
                                        {{ $commitMessage }}
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
                                <tr>
                                    <td style="border-radius:14px; background-color:#1E5A45;">
                                        <a href="{{ $reviewUrl }}" target="_blank"
                                            style="display:inline-block; padding:14px 32px; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:14px;">
                                            Review and approve
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:24px 40px; border-top:1px solid #EFEAE0;">
                            <p style="margin:0; font-size:12px; color:#9AA79F; text-align:center;">
                                &copy; {{ date('Y') }} Novix &middot; Your family's health records, organized and secure.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
