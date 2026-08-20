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

## Live components

To use the new live component system provided by symfony, add this code to your product card : 

```twig
{{ component('WishList:WishListButton', {pseId: this.defaultPseId}) }}
```

It's display whislist button and manage wishlist modal. 

To provide feedback to the user after each wishlist update, you must add this component to the bottom of base.html.twig

```twig
{{ component('WishList:WishListAlert') }}
```

## Business rules enforced by `WishListService`

### Unique title per owner

`createUpdateWishList()` and `duplicateWishList()` refuse a title already used by another
wish list of the same owner, be it a customer or an anonymous session, and throw a
`WishList\Exception\DuplicateWishListTitleException`. The front controllers turn it into a
form error, and the `WishList:WishListButton` modal shows it next to the name field.

`cloneWishList()` is left out on purpose: it duplicates a list under the very same title to
build a wish list type.

### A quantity of zero removes the line

`addProduct()` with a quantity of `0` (or `null`) removes the product from the list instead
of storing a line with an empty quantity, so a quantity input can drive both operations.

### Stock guards when a wish list goes to the cart

`addWishListToCart()` and `createCartFromWishlist()` return the lines that could not reach
the cart as requested, as an array of `WishList\Dto\RejectedWishListProduct`:

| Reason | Effect |
|---|---|
| `product_sale_element_missing` | line skipped |
| `product_not_visible` | line skipped |
| `out_of_stock` | line skipped |
| `stock_limited` | quantity reduced to `acceptedQuantity` |

Stock is only looked at when the shop checks availability (`check-available-stock`
configuration) and the product is not virtual, which mirrors what the core does on cart
quantity updates. A shop that does not track stock keeps every line.

Both API endpoints return that list under a `rejectedProducts` key.
