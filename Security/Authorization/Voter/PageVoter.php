<?php

namespace Symbio\OrangeGate\PageBundle\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PageVoter implements VoterInterface
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function supportsAttribute($attribute)
    {
        return $attribute === 'EDIT' || $attribute === 'DELETE';
    }

    public function supportsClass($class)
    {
        $supportedClass = 'Symbio\OrangeGate\PageBundle\Entity\Page';

        return $supportedClass === $class || is_subclass_of($class, $supportedClass);
    }

    /**
     * @var \Symbio\OrangeGate\PageBundle\Entity\Page $page
     */
    public function vote(TokenInterface $token, $page, array $attributes)
    {
        if (!$this->supportsClass(get_class($page))) {
            return self::ACCESS_ABSTAIN;
        }

        /**
         * @var AuthorizationChecker $authorizationChecker
         */
        $authorizationChecker = $this->container->get('security.authorization_checker');

        if ($authorizationChecker->isGranted('ROLE_SONATA_PAGE_ADMIN_PAGE_ADMIN')) {
            return self::ACCESS_GRANTED;
        }

        // check parents
        $parent = $page->getParent();
        while ($parent) {
            if ($authorizationChecker->isGranted($attributes, $parent)) {
                return self::ACCESS_GRANTED;
            }

            $parent = $parent->getParent();
        }

        return self::ACCESS_ABSTAIN;
    }
}
