# Module Wish List Thelia 2

This module allows you to create a wish list containing your favorite products.

## How to install

This module must be into your ```modules/``` directory (thelia/local/modules/).

You can download the .zip file of this module or create a git submodule into your project like this :

```
cd /path-to-thelia
git submodule add https://github.com/thelia-modules/Wishlist.git local/modules/WishList
```

Next, go to your Thelia admin panel for module activation.

## How to use

This module is very easy to use. It provides you a new loop "wishlist" type, which will list all the products added to your wish list.

To add a product in a wish list, you must give access to a link which should be "/wishlist/remove/PRODUCT_ID".
To remove a product from the wish list, you must give access to a link which should be "/wishlist/remove/PRODUCT_ID".

The argument ```PRODUCT_ID``` corresponds to the product id to add or remove from your wish list.

A new Smarty function is available to verify if a product is already in wish list : __{in_wishlist product_id="PRODUCT_ID"}__

```html
{* $ID = product ID *}

{if {in_wishlist product_id="$ID"}}
    <a href="{url path="/wishlist/remove/$ID"}">{intl l="Remove from wish list"}</a>
{else}
    <a href="{url path="/wishlist/add/$ID"}">{intl l="Add to wish list"}</a>
{/if}
```

> Caution: Adding and deleting a product to a wish list can only be done by a connected client.
> Indeed, his wish list remains private and will always be available on his personal space as it will not have it emptied.

Here is an example of using the "wishlist" loop :

```html
{loop name="wishlist" type="wishlist" customer="{customer attr="id"}"}
    {loop name="products-in-wishlist" type="product" id="{$WISHLIST_PRODUCT_LIST}"}
        <h1>{$TITLE}</h1>
        <p>{$DESCRIPTION|truncate:100 nofilter}</p>
        <a href="{url path="/wishlist/remove/$ID"}">{intl l="Remove from wish list"}</a>
    {/loop}
{/loop}
```