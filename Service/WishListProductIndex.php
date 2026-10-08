<?php

declare(strict_types=1);

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WishList\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;
use Thelia\Core\Security\SecurityContext;
use Thelia\Model\Map\ProductSaleElementsTableMap;
use WishList\Model\WishListProductQuery;

/**
 * Answers "is this product in one of my wish lists?" for every product card of a page with a
 * single query: the ids of the products the current owner keeps are loaded on the first
 * question and reused for the rest of the request.
 *
 * A visitor who brings no session cookie owns no list yet: nothing is queried for them, and
 * nothing here starts or writes a session.
 */
final class WishListProductIndex implements ResetInterface
{
    /** @var array<int, true>|null */
    private ?array $productIds = null;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly SecurityContext $securityContext,
    ) {
    }

    public function contains(int $productId): bool
    {
        $this->productIds ??= $this->load();

        return isset($this->productIds[$productId]);
    }

    public function reset(): void
    {
        $this->productIds = null;
    }

    /**
     * @return array<int, true>
     */
    private function load(): array
    {
        $request = $this->requestStack->getMainRequest();

        if (null === $request || !$request->hasSession()) {
            return [];
        }

        $session = $request->getSession();

        // Reading the customer of a session nobody started would start it.
        $customerId = $session->isStarted() ? $this->securityContext->getCustomerUser()?->getId() : null;
        $sessionId = null;

        if (null === $customerId) {
            // A session born with this request cannot own a list yet.
            if (!$request->hasPreviousSession() || !$session->isStarted() || '' === $session->getId()) {
                return [];
            }

            $sessionId = $session->getId();
        }

        $query = WishListProductQuery::create()
            ->useWishListQuery()
                ->_if(null !== $customerId)
                    ->filterByCustomerId($customerId)
                ->_else()
                    ->filterBySessionId($sessionId)
                ->_endif()
            ->endUse()
            ->joinProductSaleElements()
            ->select([ProductSaleElementsTableMap::COL_PRODUCT_ID])
            ->distinct();

        $productIds = [];

        foreach ($query->find() as $productId) {
            $productIds[(int) $productId] = true;
        }

        return $productIds;
    }
}
