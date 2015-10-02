<?php

namespace Symbio\OrangeGate\PageBundle\Listener;

use Doctrine\ORM\EntityRepository;
use Symbio\OrangeGate\PageBundle\Entity\Redirect;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RedirectListener implements EventSubscriberInterface
{

    /**
     * @var EntityRepository
     */
    protected $redirectRepository;

    /**
     * Constructor.
     *
     * @param EntityRepository $redirectRepository       Rediretct repository
     */
    public function __construct(EntityRepository $redirectRepository)
    {
        $this->redirectRepository = $redirectRepository;
    }

    /**
     * Filter the `kernel.request` event to catch redirect addresses
     *
     * @param GetResponseEvent $event
     *
     * @return void
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            // don't do anything if it's not the master request
            return;
        }

        $request = $event->getRequest();

        $redirects = $this->redirectRepository->findBy(array('enabled' => true), array('position' => 'ASC'));

        /** @var Redirect $redirect */
        foreach ($redirects as $redirect) {
            if ($destinationUrl = $this->redirectMatchesRequest($redirect, $request)) {
                $event->setResponse(new RedirectResponse($destinationUrl, $redirect->getType()));
                return;
            }
        }

        return;
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
        if (isset($matches[1]) && count($matches[1] > 0)) {
            $replacements = array();

            foreach ($matches[1] as $k => $m) {
                $replacements['$'.($k + 1)] = $m;
            }

            $destination_url = strtr($destination_url, $replacements);
        }

        if (false === strpos($destination_url, '://')) {
            $destination_url = $request->getBaseUrl().$destination_url;
        }

        return $destination_url;
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array(array('onKernelRequest', 64))
        );
    }
}
