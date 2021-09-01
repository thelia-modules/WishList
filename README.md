# Module Wish List Thelia 2

This module allows you to create a wish list containing your favorite products.

## How to install

```
composer require thelia/wishlist-module dev-main
```

Next, go to your Thelia admin panel for module activation.

## How to use

This module is very easy to use. It provides you a new loop "wishlist" type, which will list all the products added to your wish list.

To add a product in a wish list, you must give access to a link which should be "/wishlist/add/PRODUCT_ID".
To remove a product from the wish list, you must give access to a link which should be "/wishlist/remove/PRODUCT_ID".
To clear all product from the wish list, you must give access to a link which should be "/wishlist/clear".

The argument ```PRODUCT_ID``` corresponds to the product id to add or remove from your wish list.

Tow Smarty functions are availables :

- to verify if a product is already in wish list : __{in_wishlist product_id="PRODUCT_ID"}__

```html
{* $ID = product ID *}

{if {in_wishlist product_id="$ID"}}
    <a href="{url path="/wishlist/remove/$ID"}">{intl l="Remove from wish list"}</a>
{else}
    <a href="{url path="/wishlist/add/$ID"}">{intl l="Add to wish list"}</a>
{/if}
```

Here is an example of using the "wishlist" loop :

```html
{loop name="wishlist" type="wishlist"}
    {loop name="products-in-wishlist" type="product" id="{$WISHLIST_PRODUCT_LIST}"}
        <h1>{$TITLE}</h1>
        <p>{$DESCRIPTION|truncate:100 nofilter}</p>
        <a href="{url path="/wishlist/remove/$ID"}">{intl l="Remove from wish list"}</a>
    {/loop}
{/loop}
```
