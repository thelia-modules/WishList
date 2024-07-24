# Module Wish List Thelia 2

This module allows you to create multiple wish list containing your favorite products.

## How to install

```
composer require thelia/wishlist-module dev-main
```

Next, go to your Thelia admin panel for module activation.

## How to use

This module is very easy to use. It provides you new loops "wish_list" and "wish_list_product" type, which will list all the products added to your wish list.

This module provides API routes to create and manipulates wish lists, you can check them on the OpenApi documentation page,
on `/open_api/doc` in the `WishList` section.

Two Smarty functions are availables :

- to verify if a product/PSE is already in a wish list (a specific one, or one of the user's wislists) : `{in_wishlist pse_id=...|product_id=... [wish_list_id=...]}`

```html
{* $ID = product ID *}

{if {in_wishlist product_id="$ID" wish_list_id="$WHISH_LIST_ID"}}
    <a href="{url path="/wishlist/remove/$ID"}">{intl l="Remove from wish list"}</a>
{else}
    <a href="{url path="/wishlist/add/$ID"}">{intl l="Add to wish list"}</a>
{/if}
```

Here is an example of using the "wishlist" loop :

```html
{loop name="wishlist" type="wish_list"}
    {loop name="wishlistproduct" type="wish_list_product" wish_list_id=$ID}
        {loop name="products-in-wishlist" type="product" id="{$PRODUCT_ID}"}
            <h1>{$TITLE}</h1>
            <p>{$DESCRIPTION|truncate:100 nofilter}</p>
            <a href="{url path="/wishlist/remove/$ID"}">{intl l="Remove from wish list"}</a>
        {/loop}
    {/loop}
{/loop}
```

The `customer_id` argument in `wishlist` loop is allowed only if `backend_context`
is 1, otherwise the current user ID is considered.
