<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vaccination reminder — Novix</title>
</head>
<body style="margin:0; padding:0; background-color:#FBF6EA; font-family: -apple-system, 'Helvetica Neue', Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FBF6EA; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px; background-color:#ffffff; border-radius:24px; overflow:hidden; box-shadow: 0 12px 32px -12px rgba(30,90,69,0.18);">

                    <tr>
                        <td style="background-color:#14503F; padding:32px 40px;">
                            <span style="font-size:20px; font-weight:800; color:#ffffff; letter-spacing:-0.02em;">Novix</span>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:40px;">
                            <p style="margin:0 0 4px; font-size:13px; font-weight:700; color:#14503F; text-transform:uppercase; letter-spacing:0.06em;">
                                Vaccination {{ $overdue ? 'overdue' : 'reminder' }}
                            </p>
                            <h1 style="margin:0 0 16px; font-size:24px; font-weight:800; color:#1F2A24; line-height:1.3;">
                                {{ $familyMemberName }}'s next {{ $vaccineName }} dose {{ $overdue ? 'was due' : 'is due' }} {{ $dueDate->format('F j, Y') }}
                            </h1>
                            <p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#1F2A24;">
                                @if($overdue)
                                    This dose is now overdue — it's worth checking with a doctor about rescheduling.
                                @else
                                    Dose {{ $doseNumber }} is coming up within the next week.
                                @endif
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 8px;">
                                <tr>
                                    <td style="border-radius:14px; background-color:#14503F;">
                                        <a href="{{ $manageUrl }}" target="_blank"
                                            style="display:inline-block; padding:14px 32px; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:14px;">
                                            View vaccinations
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

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
