<?php

namespace WishList\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Thelia\Form\BaseForm;

class CreateUpdateWishListForm extends BaseForm
{
    public const FORM_NAME = 'wishlist_create_update_form';

    protected function buildForm(): void
    {
        $this->formBuilder->add('title', TextType::class);
    }

    public static function getName(): string
    {
        return self::FORM_NAME;
    }
}
