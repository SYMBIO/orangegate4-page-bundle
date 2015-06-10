<?php

namespace Symbio\OrangeGate\PageBundle\CmsManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;
use Sonata\PageBundle\CmsManager\CmsManagerSelector as BaseCmsManagerSelector;

class CmsManagerSelector extends BaseCmsManagerSelector
{
    /**
     * @return SessionInterface
     */
    private function getSession()
    {
        return $this->container->get('session');
    }

    /**
     * @return SecurityContextInterface
     */
    private function getSecurityContext()
    {
        return $this->container->get('security.context');
    }

    /**
     * {@inheritdoc}
     */
    public function isEditor()
    {
        return $this->getSession()->get('sonata/page/isEditor', false);
    }

    /**
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        if ($this->getSecurityContext()->getToken() && $this->getSecurityContext()->isGranted('ROLE_SONATA_ADMIN')) {
            $this->getSession()->set('sonata/page/isEditor', true);
        }
    }
}
