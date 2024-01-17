<?php

namespace WishList\Form;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Thelia\Form\BaseForm;

class AddWishListProductForm extends BaseForm
{
    protected function buildForm()
    {
        $this->formBuilder
            ->add('wishListId', IntegerType::class)
            ->add('quantity', IntegerType::class);
    }
}
