<?php
/**
 * Created by PhpStorm.
 * User: martin.cajthaml
 * Date: 16.11.15
 * Time: 1:38
 */

namespace Symbio\OrangeGate\PageBundle\Listener;


use Gedmo\Translatable\TranslatableListener;
use Symbio\OrangeGate\PageBundle\Entity\SitePool;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LateRequestListener
{
    /**
     * @var TranslatableListener
     */
    protected $translatableListener;

    /**
     * @var SitePool
     */
    protected $sitePool;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * LateRequestListener constructor.
     * @param TranslatableListener $translatableListener
     * @param SitePool $sitePool
     */
    public function __construct(TranslatableListener $translatableListener, SitePool $sitePool, TokenStorageInterface $tokenStorage)
    {
        $this->translatableListener = $translatableListener;
        $this->sitePool = $sitePool;
        $this->tokenStorage = $tokenStorage;
    }

    public function onLateKernelRequest(GetResponseEvent $event)
    {
        if ($this->tokenStorage->getToken() && $this->tokenStorage->getToken()->getUser()) {
            $site = $this->sitePool->getCurrentSite($event->getRequest());
            if ($site) {
                $this->translatableListener->setTranslatableLocale($site->getLocale());
            }
        }
    }
}