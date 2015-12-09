<?php

namespace Symbio\OrangeGate\PageBundle\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PageVoter implements VoterInterface
{
    protected $container;
    protected $onHold = false;

    const PAGE_CLASS = 'Symbio\OrangeGate\PageBundle\Entity\Page';
    const PAGE_ADMIN_CLASS = 'Symbio\OrangeGate\PageBundle\Admin\PageAdmin';
    const PROXY_PAGE_CLASS = 'Proxies\__CG__\Symbio\OrangeGate\PageBundle\Entity\Page';

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
        $supportedClasses = array(
            self::PAGE_CLASS,
            self::PAGE_ADMIN_CLASS,
            self::PROXY_PAGE_CLASS,
        );

        return in_array($class, $supportedClasses) || self::isSupportedSubclass($class, $supportedClasses);
    }

    protected static function isSupportedSubclass($class, array $supportedClasses)
    {
        foreach($supportedClasses as $supportedClass) {
            if (is_subclass_of($class, $supportedClass)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @var Object $object
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if ($this->onHold || !$this->supportsClass(get_class($object))) {
            return self::ACCESS_ABSTAIN;
        }

        $this->onHold = true;

        $controllerName = $this->container->get('request')->attributes->get('_controller');
        $actionName = substr($controllerName, strpos($controllerName, '::') + 2, -6);

        /**
         * @var AuthorizationChecker $authorizationChecker
         */
        $authorizationChecker = $this->container->get('security.authorization_checker');

        if (
            $authorizationChecker->isGranted($attributes, $object)
            || $authorizationChecker->isGranted('ROLE_SONATA_PAGE_ADMIN_PAGE_ADMIN', $object)
            || (get_class($object) == self::PAGE_ADMIN_CLASS
                && in_array($actionName, array('compose','create'))
                && $authorizationChecker->isGranted('ROLE_SONATA_PAGE_ADMIN_PAGE_GUEST', $object)
            )
        ) {
            $this->onHold = false;
            return self::ACCESS_GRANTED;
        }

        // check parents
        if (in_array(get_class($object), array(self::PAGE_CLASS,self::PROXY_PAGE_CLASS))) {
            $parent = $object->getParent();
            while ($parent) {
                if ($authorizationChecker->isGranted($attributes, $parent)) {
                    $this->onHold = false;
                    return self::ACCESS_GRANTED;
                }

                $parent = $parent->getParent();
            }
        }

        $this->onHold = false;
        return self::ACCESS_ABSTAIN;
    }
}
