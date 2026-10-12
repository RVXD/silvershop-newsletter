<!DOCTYPE html>
<html lang="$ContentLocale">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>$Title &middot; $ShopName</title>
    <style>
        body{margin:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;color:#333;line-height:1.5}
        .wrap{max-width:560px;margin:40px auto;background:#fff;border-radius:8px;padding:32px 36px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
        h1{margin:0 0 4px;font-size:22px}
        h2{margin:1.5rem 0 .5rem;font-size:18px}
        p{margin:.5rem 0}
        .muted{color:#888;font-size:13px;margin-top:2rem}
        .muted a{color:#888}
        label{display:block;font-weight:bold;margin:.6rem 0 .2rem;font-size:14px}
        input[type=text],input[type=email]{width:100%;box-sizing:border-box;padding:8px;border:1px solid #ccc;border-radius:4px;font-size:14px}
        button{background:#f5a623;color:#222;border:0;font-weight:bold;padding:11px 22px;border-radius:4px;cursor:pointer;font-size:14px;margin-top:1rem}
        .message{padding:10px 14px;border-radius:4px;margin:1rem 0}
        .message.good{background:#e6f4ea;color:#1e7e34}
        .message.bad,.message.error,.message.required{background:#fdecea;color:#b71c1c}
        .newsletter-signup__hp{position:absolute;left:-9999px}
        .field{margin-bottom:.4rem}
    </style>
</head>
<body>
    <div class="wrap">
        <h1>$ShopName</h1>

        <% if $IsConfirmed %>
            <h2><%t SilverShop\Newsletter.Confirmed "You're subscribed" %></h2>
            <p><%t SilverShop\Newsletter.ConfirmedMsg "Thanks for confirming — you'll now receive our newsletter." %></p>
        <% else_if $IsUnsubscribed %>
            <h2><%t SilverShop\Newsletter.Unsubscribed "You've been unsubscribed" %></h2>
            <p><%t SilverShop\Newsletter.UnsubscribedMsg "You won't receive our newsletter anymore. Sorry to see you go." %></p>
        <% else_if $IsInvalid %>
            <h2><%t SilverShop\Newsletter.Invalid "This link is not valid" %></h2>
            <p><%t SilverShop\Newsletter.InvalidMsg "This confirmation or unsubscribe link has expired or is incorrect." %></p>
        <% else_if $IsPreferences %>
            <h2><%t SilverShop\Newsletter.Preferences "Email preferences" %></h2>
            <p><%t SilverShop\Newsletter.PreferencesMsg "Choose whether you'd like to receive our newsletter." %></p>
            $PreferencesForm
        <% else %>
            <h2><%t SilverShop\Newsletter.SignupHeading "Subscribe to our newsletter" %></h2>
            <p><%t SilverShop\Newsletter.SignupIntro "Get news and offers in your inbox. Unsubscribe any time." %></p>
            $SignupForm
        <% end_if %>
    </div>
</body>
</html>
