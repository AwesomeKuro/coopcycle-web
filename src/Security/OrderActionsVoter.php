<?php

namespace AppBundle\Security;

use AppBundle\Entity\Sylius\Order;
use AppBundle\Entity\User;
use AppBundle\Security\TokenStoreExtractor;
use AppBundle\Sylius\Order\OrderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2Token;
use Webmozart\Assert\Assert;

class OrderActionsVoter extends Voter
{
    const ACCEPT  = 'accept';
    const REFUSE  = 'refuse';
    const DELAY   = 'delay';
    const FULFILL = 'fulfill';
    const CANCEL  = 'cancel';
    const VIEW    = 'view';
    const VIEW_PUBLIC = 'view_public';

    private static $actions = [
        self::ACCEPT,
        self::REFUSE,
        self::DELAY,
        self::FULFILL,
        self::CANCEL,
        self::VIEW,
        self::VIEW_PUBLIC,
    ];

    private $authorizationChecker;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStoreExtractor $tokenExtractor)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenExtractor = $tokenExtractor;
    }

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, self::$actions)) {
            return false;
        }

        if (!$subject instanceof Order) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if (self::VIEW_PUBLIC === $attribute) {

            $orderState = $subject->getState();

            $validStates = [
                OrderInterface::STATE_NEW,
                OrderInterface::STATE_ACCEPTED,
            ];

            if (!in_array($orderState, $validStates)) {
                return false;
            }

            return true;
        }

        if ($token instanceof OAuth2Token) {

            if (!$this->authorizationChecker->isGranted('ROLE_OAUTH2_ORDERS')) {
                return false;
            }

            if (!$subject->hasVendor()) {
                return false;
            }

            if (self::VIEW === $attribute || self::ACCEPT === $attribute) {

                if ($shop = $this->tokenExtractor->extractShop()) {

                    return $shop === $subject->getRestaurant();
                }
            }

            return false;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return false;
        }

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return true;
        }

        Assert::isInstanceOf($user, User::class);

        $ownsRestaurant = $this->isGrantedRestaurant($subject);

        $isCustomer = null !== $subject->getCustomer()
            && $subject->getCustomer()->hasUser()
            && $subject->getCustomer()->getUser() === $user;

        $dispatcher = $this->authorizationChecker->isGranted('ROLE_DISPATCHER');

        if (self::VIEW === $attribute) {
            return $ownsRestaurant || $isCustomer || $dispatcher;
        }

        // For actions like "accept", "refuse", etc...
        return $ownsRestaurant || $dispatcher;
    }

    private function isGrantedRestaurant($subject)
    {
        foreach ($subject->getRestaurants() as $restaurant) {
            if ($this->authorizationChecker->isGranted('edit', $restaurant)) {
                return true;
            }
        }

        return false;
    }
}
