<%-- Footer / sidebar newsletter signup. Drop it anywhere in a theme with
         <% include SilverShop/Newsletter/NewsletterSignup %>
     It renders the globally-available $NewsletterSignupForm (see NewsletterTemplateProvider). Override this file
     in your theme to restyle. Units are em (not rem): the startup theme sets html { font-size: 62.5% }, so rem
     would render tiny inside a module <style> block. --%>
<div class="newsletter-signup">
    <style>
        .newsletter-signup { margin: 1em 0; }
        .newsletter-signup__heading { font-weight: 600; margin: 0 0 .5em; }
        .newsletter-signup__intro { opacity: .8; margin: 0 0 .75em; font-size: .95em; }
        .newsletter-signup form { display: flex; flex-wrap: wrap; gap: .5em; align-items: flex-end; }
        .newsletter-signup .field { flex: 1 1 14em; margin: 0; }
        .newsletter-signup input[type=email],
        .newsletter-signup input[type=text] { width: 100%; box-sizing: border-box; padding: .6em .7em; }
        .newsletter-signup .checkbox { flex: 1 1 100%; font-size: .9em; }
        .newsletter-signup .btn-toolbar { flex: 0 0 auto; margin: 0; }
        .newsletter-signup__hp { position: absolute !important; left: -9999px !important; }
    </style>
    <p class="newsletter-signup__heading"><%t SilverShop\Newsletter.SignupHeading "Subscribe to our newsletter" %></p>
    <p class="newsletter-signup__intro"><%t SilverShop\Newsletter.SignupIntro "Get news and offers in your inbox. Unsubscribe any time." %></p>
    $NewsletterSignupForm
</div>
