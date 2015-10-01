<?php

namespace Symbio\OrangeGate\PageBundle\Listener;

use Doctrine\ORM\EntityRepository;
use Sonata\PageBundle\CmsManager\CmsManagerSelectorInterface;
use Sonata\PageBundle\Site\SiteSelectorInterface;
use Sonata\PageBundle\Exception\InternalErrorException;
use Sonata\PageBundle\Exception\PageNotFoundException;
use Sonata\PageBundle\CmsManager\DecoratorStrategyInterface;
use Sonata\PageBundle\Model\PageInterface;

use Symbio\OrangeGate\PageBundle\Entity\Redirect;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Request;

class RequestListener extends \Sonata\PageBundle\Listener\RequestListener
{

    /**
     * @var EntityRepository
     */
    protected $redirectRepository;

    /**
     * Constructor.
     *
     * @param CmsManagerSelectorInterface $cmsSelector       Cms manager selector
     * @param SiteSelectorInterface       $siteSelector      Site selector
     * @param DecoratorStrategyInterface  $decoratorStrategy Decorator strategy
     */
    public function __construct(CmsManagerSelectorInterface $cmsSelector, SiteSelectorInterface $siteSelector, DecoratorStrategyInterface $decoratorStrategy, EntityRepository $redirectRepository)
    {
        parent::__construct($cmsSelector, $siteSelector, $decoratorStrategy);

        $this->redirectRepository = $redirectRepository;
    }

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

        $redirects = $this->redirectRepository->findBy(array('enabled' => true), array('position' => 'ASC'));

        /** @var Redirect $redirect */
        foreach ($redirects as $redirect) {
            if ($destinationUrl = $this->redirectMatchesRequest($redirect, $request)) {
                $event->setResponse(new RedirectResponse($destinationUrl));
                return;
            }
        }

        $cms = $this->cmsSelector->retrieve();
        if (!$cms) {
            throw new InternalErrorException('No CMS Manager available');
        }

        $site = $this->siteSelector->retrieve();

        if (!$site) {
            return;
            throw new InternalErrorException('No site available for the current request with uri '.htmlspecialchars($request->getUri(), ENT_QUOTES));
        }

        if ($site->getLocale() && $site->getLocale() != $request->get('_locale')) {
            throw new PageNotFoundException(sprintf('Invalid locale - site.locale=%s - request._locale=%s', $site->getLocale(), $request->get('_locale')));
        }

        $request->setLocale($site->getLocale());

        // true cms page
        if ($request->get('_route') == PageInterface::PAGE_ROUTE_CMS_NAME) {
            return;
        }

        if (!$this->decoratorStrategy->isRequestDecorable($request)) {
            return;
        }

        try {
            $page = $cms->getPageByRouteName($site, $request->get('_route'));

            if (!$page->getEnabled() && !$this->cmsSelector->isEditor()) {
                throw new PageNotFoundException(sprintf('The page is not enabled : id=%s', $page->getId()));
            }

            $cms->setCurrentPage($page);

        } catch (PageNotFoundException $e) {
            return;
        }
    }

    /**
     * This method validates a redirect against request. If they match,
     * returns destination URL for redirection.
     *
     * @param Request $request
     * @return bool|string
     */
    protected function redirectMatchesRequest(Redirect $redirect, Request $request)
    {
        $source_url = $redirect->getSourceUrl();
        $destination_url = $redirect->getDestinationUrl();
        $match_host = false;

        if (true === strpos($source_url, '://')) {
            $match_host = true;
            $parsed_src = parse_url($source_url);
        }

        // test hostname first
        if ($match_host && !preg_match('/^'.preg_quote($parsed_src['host'], '/').'$/', $request->getHost())) {
            return false;
        }

        $requested_url = $request->getPathInfo();
        if ($request->getQueryString()) {
            $requested_url .= '?'.$request->getQueryString();
        }

        if ($match_host) {
            $source_regex = $parsed_src['path'];
        } else {
            $source_regex = $source_url;
        }

        // prepare regexp
        $source_regex = $source_regex;
        $source_regex = str_replace('/', '\\/', $source_regex);
        $source_regex = str_replace('?', '\\?', $source_regex);

        $found = preg_match_all('/^'.$source_regex.'.*$/', $requested_url, $matches);

        if (!$found) {
            return false;
        }

        // replace tokens
        if (count($matches[0]) > 0) {
            $replacements = array();

            foreach ($matches[1] as $k => $m) {
                $replacements['$'.($k + 1)] = $m;
            }

            $destination_url = strtr($destination_url, $replacements);
        }

        $destination_url = $request->getBaseUrl().$destination_url;

        return $destination_url;
    }
}
