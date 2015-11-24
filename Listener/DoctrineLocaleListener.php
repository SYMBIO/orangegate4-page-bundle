<?php
/**
 * Created by PhpStorm.
 * User: martin.cajthaml
 * Date: 16.11.15
 * Time: 1:38
 */

namespace Symbio\OrangeGate\PageBundle\Listener;


use Gedmo\Translatable\TranslatableListener;
use Sonata\PageBundle\Site\SiteSelectorInterface;
use Symbio\OrangeGate\PageBundle\Entity\SitePool;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class DoctrineLocaleListener
{
    /**
     * @var TranslatableListener
     */
    protected $translatableListener;

    /**
     * @var SiteSelectorInterface
     */
    protected $siteSelector;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * LateRequestListener constructor.
     * @param TranslatableListener $translatableListener
     * @param SitePool $sitePool
     */
    public function __construct(TranslatableListener $translatableListener, SiteSelectorInterface $siteSelector)
    {
        $this->translatableListener = $translatableListener;
        $this->siteSelector = $siteSelector;
    }

    public function onLateKernelRequest(GetResponseEvent $event)
    {
        $site = $this->siteSelector->retrieve();
        if ($site) {
            $this->translatableListener->setTranslatableLocale($site->getLocale());
        }
    }
}