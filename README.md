# Module Wish List Thelia 2

This module allows you to create multiple wish list containing your favorite products.

## How to install

```
composer require thelia/wishlist-module dev-main
```

Next, go to your Thelia admin panel for module activation.

## How to use

The wish lists are exposed through the API Platform resources (`/api/front/wish-lists`, `/api/front/wish-list-products`) and the live components below.

A Twig function tells whether a product or a product sale element is already in a wish list (a specific one, or one of the user's wish lists):

```twig
{% if in_wishlist(null, wishListId, product.id) %}
    ...
{% endif %}
```

The arguments are, in this order, the product sale element id, the wish list id (optional) and the product id (used to find the default product sale element when the first one is null).

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
