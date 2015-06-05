<?php

namespace Symbio\OrangeGate\PageBundle\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Acl\Voter\AclVoter;

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

        $securityContext = $this->container->get('security.context');

        // check parents
        $parent = $page->getParent();
        while ($parent) {
            if ($securityContext->isGranted($attributes, $parent)) {
                return self::ACCESS_GRANTED;
            }

            $parent = $parent->getParent();
        }

        return self::ACCESS_DENIED;
    }
}
