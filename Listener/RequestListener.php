<?php

namespace Symbio\OrangeGate\PageBundle\Listener;

use Sonata\PageBundle\CmsManager\CmsManagerSelectorInterface;
use Sonata\PageBundle\Site\SiteSelectorInterface;
use Sonata\PageBundle\Exception\InternalErrorException;
use Sonata\PageBundle\Exception\PageNotFoundException;
use Sonata\PageBundle\CmsManager\DecoratorStrategyInterface;
use Sonata\PageBundle\Model\PageInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListener extends \Sonata\PageBundle\Listener\RequestListener
{

    /**
     * Filter the `core.request` event to decorated the action
     *
     * @param GetResponseEvent $event
     *
     * @return void
     *
     * @throws InternalErrorException
     * @throws PageNotFoundException
     */
    public function onCoreRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $cms = $this->cmsSelector->retrieve();
        if (!$cms) {
            throw new InternalErrorException('No CMS Manager available');
        }

        $site = $this->siteSelector->retrieve();

        $isRequestDecorable = $this->decoratorStrategy->isRequestDecorable($request);

        if (!$site) {
            if ($isRequestDecorable) {
                throw new InternalErrorException('No site available for the current request with uri '.htmlspecialchars($request->getUri(), ENT_QUOTES));
            } else {
                return;
            }
        }

        if ($site->getLocale() && $site->getLocale() != $request->get('_locale')) {
            throw new PageNotFoundException(sprintf('Invalid locale - site.locale=%s - request._locale=%s', $site->getLocale(), $request->get('_locale')));
        }

        $request->setLocale($site->getLocale());

        // true cms page
        if ($request->get('_route') === PageInterface::PAGE_ROUTE_CMS_NAME) {
            return;
        }

        if (!$isRequestDecorable) {
            return;
        }

        $page = $cms->getPageByRouteName($site, $request->get('_route'));

        if (!$page->getEnabled() && !$this->cmsSelector->isEditor()) {
            throw new PageNotFoundException(sprintf('The page is not enabled : id=%s', $page->getId()));
        }

        $cms->setCurrentPage($page);
    }
}
