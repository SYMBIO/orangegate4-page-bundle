<?php

namespace Symbio\OrangeGate\PageBundle\Page;

use Symbio\OrangeGate\PageBundle\Entity\LanguageVersion;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        if ($page->getTarget()) {
            return new RedirectResponse($page->getTarget()->getUrl());
        }

        $this->updateSeoPage($page, $request);

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
    protected function updateSeoPage(PageInterface $page, Request $request)
    {
        /**
         * @var LanguageVersion $languageVersion
         */
        $languageVersion = $page->getSite()->getLanguageVersion($request->getLocale());
        $siteTitle = $languageVersion->getTitle() ?: $page->getSite()->getName();

        if (!$page->getParent()) {
            $title = $siteTitle;
        } else {
            if ($page->getTitle()) {
                $title = $page->getTitle().' - '.$siteTitle;
            } elseif ($page->getName()) {
                $title = $page->getName().' - '.$siteTitle;
            } else {
                $title = $siteTitle;
            }
        }
        $this->seoPage->setTitle($title);
        $this->seoPage->addMeta('property', 'og:title', $title);

        if ($page->getMetaDescription()) {
            $this->seoPage->addMeta('name', 'description', $page->getMetaDescription());
            $this->seoPage->addMeta('property', 'og:description', $page->getMetaDescription());
        } elseif ($languageVersion->getMetaDescription()) {
            $this->seoPage->addMeta('name', 'description', $languageVersion->getMetaDescription());
            $this->seoPage->addMeta('property', 'og:description', $languageVersion->getMetaDescription());
        }

        if ($page->getMetaKeyword()) {
            $this->seoPage->addMeta('name', 'keywords', $page->getMetaKeyword());
        } elseif ($languageVersion->getMetaKeywords()) {
            $this->seoPage->addMeta('name', 'keywords', $languageVersion->getMetaKeywords());
        }

        if ($page->getOgImage()) {
            //$ogImageUrl = $this->get('sonata.media.twig.extension')->path($ogImage, 'og_image'); TODO
            $this->seoPage->addMeta('property', 'og:image', strpos($page->getOgImage(), '://') !== false ? $page->getOgImage() : sprintf('%s://%s%s', isset($_SERVER['HTTPS']) ? 'https' : 'http', $_SERVER['HTTP_HOST'], $page->getOgImage()));
        }

        $this->seoPage->addMeta('property', 'og:site_name', $languageVersion->getTitle());
        $this->seoPage->addMeta('property', 'og:url', $request->getUri());
        $this->seoPage->addMeta('property', 'og:type', 'website');
        $this->seoPage->addHtmlAttributes('prefix', 'og: http://ogp.me/ns#');
    }
}
