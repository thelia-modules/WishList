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

namespace WishList\Api\Extension;

use ApiPlatform\Metadata\Operation;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Thelia\Api\Bridge\Propel\Extension\QueryCollectionExtensionInterface;
use Thelia\Core\Security\SecurityContext;
use Thelia\Model\Customer;
use WishList\Model\WishListProductQuery;
use WishList\Model\WishListQuery;

/**
 * Restricts the front collections of wish lists and of wish list lines to the
 * caller who owns them, the way WishListService already does for every other
 * read: the current customer, or the current session for a visitor who is not
 * logged in.
 *
 * Without it the front collections answer whatever the query parameters ask
 * for, so `?customerId=<someone else>` hands that customer's lists, titles
 * included, to an unauthenticated caller.
 *
 * The admin collections are left alone: the core access map already reserves
 * `/api/admin` to ROLE_ADMIN.
 */
final readonly class FrontOwnerCollectionExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private SecurityContext $securityContext,
        private RequestStack $requestStack,
    ) {
    }

    public function applyToCollection(ModelCriteria $query, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (!$query instanceof WishListQuery && !$query instanceof WishListProductQuery) {
            return;
        }

        if (!str_starts_with((string) $operation?->getUriTemplate(), '/front/')) {
            return;
        }

        $joined = $query instanceof WishListProductQuery;
        $wishListQuery = $joined ? $query->useWishListQuery() : $query;

        $customerId = $this->currentCustomerId();
        $sessionId = null === $customerId ? $this->currentSessionId() : null;

        match (true) {
            null !== $customerId => $wishListQuery->filterByCustomerId($customerId),
            null !== $sessionId => $wishListQuery->filterBySessionId($sessionId),
            // Neither a customer nor a session: the caller owns no list at all.
            default => $wishListQuery->where('1 = 0'),
        };

        if ($joined) {
            $wishListQuery->endUse();
        }
    }

    /**
     * The API firewall is stateless, so a customer authenticated by a JWT only
     * exists in the security token; a page rendered by the front reaches the
     * same resources in-process, outside any firewall, and only has the
     * customer held by the Thelia session.
     */
    private function currentCustomerId(): ?int
    {
        $tokenUser = $this->tokenStorage->getToken()?->getUser();

        if ($tokenUser instanceof Customer) {
            return $tokenUser->getId();
        }

        return $this->securityContext->getCustomerUser()?->getId();
    }

    private function currentSessionId(): ?string
    {
        $request = $this->requestStack->getCurrentRequest() ?? $this->requestStack->getMainRequest();

        if (null === $request || !$request->hasSession()) {
            return null;
        }

        return $request->getSession()->getId();
    }
}
