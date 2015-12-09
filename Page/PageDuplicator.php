<?php

namespace Symbio\OrangeGate\PageBundle\Page;

use Doctrine\ORM\EntityManager;
use Sonata\BlockBundle\Model\BlockInterface;

use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Model\PageManagerInterface;
use Sonata\PageBundle\Model\SiteInterface;
use Sonata\PageBundle\Model\BlockInteractorInterface;
use Sonata\PageBundle\CmsManager\BaseCmsPageManager;
use Symbio\OrangeGate\PageBundle\Entity\Block;
use Symbio\OrangeGate\PageBundle\Entity\BlockTranslation;
use Symbio\OrangeGate\PageBundle\Entity\Page;
use Symbio\OrangeGate\PageBundle\Entity\PageTranslation;

/**
 * Performs deep copy of page including blocks, translations and children pages.
 * Optionaly page can be duplicated also without children pages.
 *
 * Methods will return copy that is persisted but not flushed with entity manager,
 * so can decide whether to flush or discard copied entity.
 *
 * Perhaps could be also implemented via __clone method on each of Page, Block entities,
 * but I wasn't sure, if it would cause no harm to some other code in sonata page bundle relying on these entities.
 *
 * Class PageDuplicator
 * @package Symbio\OrangeGate\PageBundle\Page
 */
class PageDuplicator
{
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Will return copy that is persisted but not flushed with entity manager,
     * so can decide whether to flush or discard copied entity.
     *
     * @param $id
     * @return Page
     */
    public function duplicatePageWithoutChildren($id)
    {
        $page = $this->entityManager->getRepository('Symbio\OrangeGate\PageBundle\Entity\Page')->findOneById($id);
        return $this->deepCopyPageWithoutChildren($page);
    }

    /**
     * Will return copy that is persisted but not flushed with entity manager,
     * so can decide whether to flush or discard copied entity.
     *
     * @param $id
     * @return Page
     */
    public function duplicatePageWithChildren($id)
    {
        $page = $this->entityManager->getRepository('Symbio\OrangeGate\PageBundle\Entity\Page')->findOneById($id);
        return $this->deepCopyPage($page);
    }

    protected function deepCopyPage(Page $page, Page $parentPage = null)
    {
        $copy = $this->deepCopyPageWithoutChildren($page, $parentPage);
        foreach($page->getChildren() as $child) {
            $childCopy = $this->deepCopyPage($child, $copy);
            $copy->addChild($childCopy);
        }
        $this->entityManager->persist($copy);
        return $copy;
    }

    protected function deepCopyPageWithoutChildren(Page $page, Page $parentPage = null)
    {
        $copy = new Page();
        $copy->setId($page->getId());
        $copy->setRouteName($page->getRouteName());
        $copy->setPageAlias($page->getPageAlias());
        $copy->setType($page->getType());
        $copy->setEnabled($page->getEnabled());
        $copy->setName($page->getName());
        $copy->setSlug($page->getSlug());
        $copy->setUrl($page->getUrl());
        $copy->setCustomUrl($page->getCustomUrl());
        $copy->setMetaKeyword($page->getMetaKeyword());
        $copy->setMetaDescription($page->getMetaDescription());
        $copy->setJavascript($page->getJavascript());
        $copy->setStylesheet($page->getStylesheet());
        $copy->setCreatedAt($page->getCreatedAt());
        $copy->setUpdatedAt($page->getUpdatedAt());
        $copy->setTarget($page->getTarget());
        $copy->setTemplateCode($page->getTemplateCode());
        $copy->setDecorate($page->getDecorate());
        $copy->setPosition($page->getPosition());
        $copy->setRequestMethod($page->getRequestMethod());
        $copy->setHeaders($page->getHeaders());
        $copy->setSite($page->getSite());
        $copy->setRawHeaders($page->getRawHeaders());
        $copy->setEdited($page->getEdited());
        $copy->setTitle($page->getTitle());
        $copy->setParent($parentPage ? $parentPage : $page->getParent());

        foreach($page->getTranslations() as  $translation) {
            $translationCopy = $this->deepCopyPageTranslation($translation, $copy);
            $copy->addTranslation($translationCopy);
        }

        foreach($page->getBlocks() as $block) {
            if (!$block->getParent()) {
                $blockCopy = $this->deepCopyBlock($block, $copy);
                $this->entityManager->persist($blockCopy);
            }
        }

        $this->entityManager->persist($copy);
        return $copy;
    }

    protected function deepCopyBlock(Block $block, Page $parentPage)
    {
        $copy = new Block();
        $copy->setSite($block->getSite());
        $copy->setPage($parentPage);
        $copy->setSettings($block->getSettings());
        $copy->setEnabled($block->getEnabled());
        $copy->setName($block->getName());
        $copy->setType($block->getType());
        $copy->setPosition($block->getPosition());

        foreach($block->getChildren() as $child) {
            $childCopy = $this->deepCopyBlock($child, $parentPage);
            $copy->addChildren($childCopy);
        }

        foreach($block->getTranslations() as $translation) {
            $translationCopy = $this->deepCopyBlockTranslation($translation, $copy);
            $copy->addTranslation($translationCopy);
        }

        return $copy;
    }


    protected function deepCopyBlockTranslation(BlockTranslation $translation, Block $parent)
    {
        $copy = new BlockTranslation();
        $copy->setLocale($translation->getLocale());
        $copy->setObject($parent);
        $copy->setEnabled($translation->getEnabled());
        $copy->setSettings($translation->getSettings());
        return $copy;
    }

    protected function deepCopyPageTranslation(PageTranslation $translation, Page $parent)
    {
        $copy = new PageTranslation();
        $copy->setLocale($translation->getLocale());
        $copy->setObject($parent);
        $copy->setEnabled($translation->getEnabled());
        $copy->setName($translation->getName());
        $copy->setDescription($translation->getDescription());
        $copy->setTitle($translation->getTitle());
        $copy->setSlug($translation->getSlug());
        $copy->setUrl($translation->getUrl());
        $copy->setCustomUrl($translation->getCustomUrl());
        $copy->setMetaKeyword($translation->getMetaKeyword());
        $copy->setMetaDescription($translation->getMetaDescription());
        return $copy;
    }

}
