<!DOCTYPE html>
<html lang="$ContentLocale">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><%t SilverShop\Newsletter.ConfirmTitle "Confirm your subscription" %></title>
</head>
<body style="margin:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;color:#333;line-height:1.5;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;background:#fff;border-radius:8px;">
                    <tr>
                        <td style="padding:32px 36px;">
                            <h1 style="margin:0 0 12px;font-size:20px;">$ShopName</h1>
                            <p><%t SilverShop\Newsletter.ConfirmGreeting "Thanks for signing up for our newsletter." %></p>
                            <p><%t SilverShop\Newsletter.ConfirmInstruction "Please confirm your subscription by clicking the button below." %></p>
                            <p style="margin:24px 0;">
                                <a href="$ConfirmLink" style="background:#f5a623;color:#222;font-weight:bold;text-decoration:none;padding:12px 24px;border-radius:4px;display:inline-block;">
                                    <%t SilverShop\Newsletter.ConfirmButton "Confirm my subscription" %>
                                </a>
                            </p>
                            <p style="font-size:12px;color:#888;">
                                <%t SilverShop\Newsletter.ConfirmFallback "If the button doesn't work, copy this link into your browser:" %><br>
                                <a href="$ConfirmLink" style="color:#888;">$ConfirmLink</a>
                            </p>
                            <p style="font-size:12px;color:#888;margin-top:24px;">
                                <%t SilverShop\Newsletter.ConfirmIgnore "If you didn't request this, you can safely ignore this email, or" %>
                                <a href="$UnsubscribeLink" style="color:#888;"><%t SilverShop\Newsletter.Unsubscribe "unsubscribe" %></a>.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
