<?php

declare(strict_types=1);

namespace WishList\Form;

use Thelia\Form\BaseForm;

/**
 * Minimal form providing CSRF protection for stateful wishlist actions
 * (delete, remove product, clear). No business fields needed — only the
 * CSRF token injected automatically by BaseForm via CsrfExtension.
 */
class WishListActionForm extends BaseForm
{
    public const FORM_NAME = 'wishlist_action_form';

    protected function buildForm(): void
    {
    }

    public static function getName(): string
    {
        return self::FORM_NAME;
    }
}
