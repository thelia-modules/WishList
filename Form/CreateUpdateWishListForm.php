<?php

namespace WishList\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Thelia\Form\BaseForm;

class CreateUpdateWishListForm extends BaseForm
{
    protected function buildForm(): void
    {
        $this->formBuilder->add('title', TextType::class);
    }
}
