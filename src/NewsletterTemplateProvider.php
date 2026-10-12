<?php

declare(strict_types=1);

namespace SilverShop\Newsletter;

use SilverShop\Newsletter\Control\NewsletterController;
use SilverStripe\Forms\Form;
use SilverStripe\View\TemplateGlobalProvider;

/**
 * Exposes the newsletter signup form in ANY template — including the theme's page-less footer/sidebar shells —
 * as $NewsletterSignupForm, so a theme can drop the signup anywhere with
 *
 *   <% include SilverShop/Newsletter/NewsletterSignup %>
 *
 * or render the form directly with $NewsletterSignupForm. Auto-registered via TemplateGlobalProvider (no YAML).
 */
class NewsletterTemplateProvider implements TemplateGlobalProvider
{
    public static function get_template_global_variables(): array
    {
        return [
            'NewsletterSignupForm' => 'NewsletterSignupForm',
        ];
    }

    public static function NewsletterSignupForm(): Form
    {
        return NewsletterController::create()->SignupForm();
    }
}
