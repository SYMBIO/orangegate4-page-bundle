<?php

namespace Symbio\OrangeGate\PageBundle\Site;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Sonata\PageBundle\Request\SiteRequestInterface;
use Sonata\PageBundle\Request\SiteRequestContext;
use Sonata\PageBundle\Site\HostPathSiteSelector as ParentSiteSelector;
use Sonata\PageBundle\Model\SiteInterface;

/**
 * HostPathSiteSelector
 */
class HostPathSiteSelector extends ParentSiteSelector
{
    /**
     * Returns TRUE whether the given site matches the given request
     *
     * @param SiteInterface $site    A site instance
     * @param Request       $request A request instance
     *
     * @return string|boolean FALSE whether the site does not match
     */
    protected function matchRequest(SiteInterface $site, Request $request)
    {
        $results = array();

        // we read the value from the attribute to handle fragment support
        $requestPathInfo = $request->get('pathInfo', $request->getPathInfo());

        foreach ($site->getLanguageVersions() as $lv) {
            if (preg_match(sprintf('@^(%s)(/.*|$)@', $lv->getRelativePath()), $requestPathInfo, $results)) {
                $site->setHost($lv->getHost());
                $site->setLocale($lv->getLocale());
                $site->setRelativePath($lv->getRelativePath());
                $site->setTitle($lv->getTitle());
                $site->setMetaDescription($lv->getMetaDescription());
                $site->setMetaKeywords($lv->getMetaKeywords());
                $this->site = $site;
                $request->setLocale($lv->getLocale());
                return $results[2];
            }

            // default language versions should be last in the list, so when we get to them
            // it's definite that no other language version matched
            if ($lv->getIsDefault()) {
                $site->setHost($lv->getHost());
                $site->setLocale($lv->getLocale());
                $site->setRelativePath($lv->getRelativePath());
                $site->setTitle($lv->getTitle());
                $site->setMetaDescription($lv->getMetaDescription());
                $site->setMetaKeywords($lv->getMetaKeywords());
                $this->site = $site;
                $request->setLocale($lv->getLocale());
                return $requestPathInfo;
            }
        }

        return false;
    }

    /**
     * @param Request $request
     *
     * @return SiteInterface[]
     */
    protected function getSites(Request $request)
    {
        return $this->siteManager->findByHost($request->getHost());
    }
}
