<?php

namespace Symbio\OrangeGate\PageBundle\Page;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Sonata\SeoBundle\Seo\SeoPageInterface;

use Sonata\PageBundle\Page\Service\BasePageService;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Page\TemplateManagerInterface;

class OrangeGatePageService extends BasePageService
{
    /**
     * @var TemplateManagerInterface
     */
    protected $templateManager;

    /**
     * @var SeoPageInterface
     */
    protected $seoPage;

    /**
     * Constructor
     *
     * @param string                    $name            Page service name
     * @param TemplateManagerInterface  $templateManager Template manager
     * @param SeoPageInterface          $seoPage         SEO page object
     */
    public function __construct($name, TemplateManagerInterface $templateManager, SeoPageInterface $seoPage = null)
    {
        $this->name            = $name;
        $this->templateManager = $templateManager;
        $this->seoPage         = $seoPage;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(PageInterface $page, Request $request, array $parameters = array(), Response $response = null)
    {
        $this->updateSeoPage($page, $request->getLocale());

        if ($content = $response->getContent()) {
            $response = $this->templateManager->renderResponse($page->getTemplateCode(), array_merge($parameters, array('content' => $content)), $response);
        } else {
            $response = $this->templateManager->renderResponse($page->getTemplateCode(), $parameters, $response);
        }

        return $response;
    }

    /**
     * Updates the SEO page values for given page instance
     *
     * @param PageInterface $page
     */
    protected function updateSeoPage(PageInterface $page, $locale)
    {
        if (!$this->seoPage) {
            return;
        }

        if (!$page->getParent()) {
            $this->seoPage->setTitle($page->getSite()->getLanguageVersion($locale)->getTitle());
        } else {
            if ($page->getTitle()) {
                $this->seoPage->setTitle($this->seoPage->getTitle().' - '.$page->getTitle());
            } elseif ($page->getName()) {
                $this->seoPage->setTitle($this->seoPage->getTitle().' - '.$page->getName());
            }
        }

        if ($page->getMetaDescription()) {
            $this->seoPage->addMeta('name', 'description', $page->getMetaDescription());
            $this->seoPage->addMeta('property', 'og:description', $page->getMetaDescription());
        } elseif ($page->getSite()->getLanguageVersion($locale)->getMetaDescription()) {
            $this->seoPage->addMeta('name', 'description', $page->getSite()->getLanguageVersion($locale)->getMetaDescription());
            $this->seoPage->addMeta('property', 'og:description', $page->getSite()->getLanguageVersion($locale)->getMetaDescription());
        }

        if ($page->getMetaKeyword()) {
            $this->seoPage->addMeta('name', 'keywords', $page->getMetaKeyword());
        } elseif ($page->getSite()->getLanguageVersion($locale)->getMetaKeywords()) {
            $this->seoPage->addMeta('name', 'keywords', $page->getSite()->getLanguageVersion($locale)->getMetaKeywords());
        }

        $this->seoPage->addMeta('property', 'og:site_name', $page->getSite()->getLanguageVersion($locale)->getTitle());
        $this->seoPage->addMeta('property', 'og:url', 'http://'.$page->getSite()->getLanguageVersion($locale)->getHost());
        $this->seoPage->addMeta('property', 'og:type', 'website');
        $this->seoPage->addHtmlAttributes('prefix', 'og: http://ogp.me/ns#');
    }
}
