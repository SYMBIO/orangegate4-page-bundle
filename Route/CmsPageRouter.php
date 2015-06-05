<?php

namespace Symbio\OrangeGate\PageBundle\Route;

use Symfony\Cmf\Component\Routing\ChainedRouterInterface;
use Symfony\Cmf\Component\Routing\VersatileGeneratorInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RouterInterface;
use Sonata\PageBundle\CmsManager\CmsManagerInterface;
use Sonata\PageBundle\Model\SiteInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

use Sonata\PageBundle\CmsManager\CmsManagerSelectorInterface;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Exception\PageNotFoundException;
use Sonata\PageBundle\Site\SiteSelectorInterface;
use Sonata\PageBundle\Route\CmsPageRouter as BaseCmsPageRouter;

class CmsPageRouter extends BaseCmsPageRouter
{
    /**
     * Generates an URL from a Page object
     *
     * @param PageInterface $page          Page object
     * @param array         $parameters    An array of parameters
     * @param bool|string   $referenceType The type of reference to be generated (one of the constants)
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function generateFromPage(PageInterface $page, array $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        if (isset($parameters['path'])) {
            unset($parameters['path']);
        }

        // hybrid pages use, by definition, the default routing mechanism
        if ($page->isHybrid()) {
            //return $this->router->generate($page->getRouteName(), $parameters, $referenceType);
        }

        $url = $this->getUrlFromPage($page);

        if ($url === false) {
            throw new \RuntimeException(sprintf('Page "%d" has no url or customUrl.', $page->getId()));
        }

        $url = $this->decorateUrl($url, $parameters, $referenceType);

        if ($page->getSite() !== $this->siteSelector->retrieve()) {
            $url = str_replace($this->siteSelector->retrieve()->getRelativePath(), $page->getSite()->getRelativePath(), $url);
        }

        return $url;
    }

    /**
     * Decorates an URL with url context and query
     *
     * @param string      $url           Relative URL
     * @param array       $parameters    An array of parameters
     * @param bool|string $referenceType The type of reference to be generated (one of the constants)
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function customDecorateUrl($site, $url, array $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        if (!$this->context) {
            throw new \RuntimeException('No context associated to the CmsPageRouter');
        }

        $schemeAuthority = '';
        if ($this->context->getHost() && (self::ABSOLUTE_URL === $referenceType || self::NETWORK_PATH === $referenceType)) {
            $port = '';
            if ('http' === $this->context->getScheme() && 80 != $this->context->getHttpPort()) {
                $port = sprintf(':%s', $this->context->getHttpPort());
            } elseif ('https' === $this->context->getScheme() && 443 != $this->context->getHttpsPort()) {
                $port = sprintf(':%s', $this->context->getHttpsPort());
            }

            $schemeAuthority = self::NETWORK_PATH === $referenceType ? '//' : sprintf('%s://', $this->context->getScheme());
            $schemeAuthority = sprintf('%s%s%s', $schemeAuthority, $this->context->getHost(), $port);
        }

        if (self::RELATIVE_PATH === $referenceType) {
            $url = $this->getRelativePath($this->context->getPathInfo(), $url);
        } else {
            $url = sprintf('%s%s%s', $schemeAuthority, str_replace($this->siteSelector->retrieve()->getRelativePath(), $site->getRelativePath(), $this->context->getBaseUrl()), $url);
        }

        if (count($parameters) > 0) {
            return sprintf('%s?%s', $url, http_build_query($parameters, '', '&'));
        }

        return $url;
    }
}
