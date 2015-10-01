<?php
/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symbio\OrangeGate\PageBundle\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Sonata\BlockBundle\Model\BlockManagerInterface;
use Sonata\PageBundle\Model\PageManagerInterface;
use Sonata\PageBundle\Model\SnapshotManagerInterface;
use Sonata\PageBundle\Model\TransformerInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Model\SnapshotInterface;
use Sonata\PageBundle\Model\SnapshotPageProxy;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Sonata\PageBundle\Entity\Transformer as BaseTransformer;

class Transformer extends BaseTransformer
{
    public function create(PageInterface $page)
    {
        $snapshot = $this->snapshotManager->create();

        $snapshot->setPage($page);
        $snapshot->setUrl($page->getUrl());
        $snapshot->setEnabled($page->getEnabled());
        $snapshot->setRouteName($page->getRouteName());
        $snapshot->setPageAlias($page->getPageAlias());
        $snapshot->setType($page->getType());
        $snapshot->setName($page->getName());
        $snapshot->setPosition($page->getPosition());
        $snapshot->setDecorate($page->getDecorate());

        if (!$page->getSite()) {
            throw new \RuntimeException(sprintf('No site linked to the page.id=%s', $page->getId()));
        }

        $snapshot->setSite($page->getSite());

        if ($page->getParent()) {
            $snapshot->setParentId($page->getParent()->getId());
        }

        if ($page->getTarget()) {
            $snapshot->setTargetId($page->getTarget()->getId());
        }

        $content                     = array();
        $content['id']               = $page->getId();
        $content['name']             = $page->getName();
        $content['javascript']       = $page->getJavascript();
        $content['stylesheet']       = $page->getStylesheet();
        $content['raw_headers']      = $page->getRawHeaders();
        $content['title']            = $page->getTitle();
        $content['description']      = $page->getDescription();
        $content['icon_id']          = $page->getIcon() ? $page->getIcon()->getId() : null;
        $content['meta_description'] = $page->getMetaDescription();
        $content['meta_keyword']     = $page->getMetaKeyword();
        $content['template_code']    = $page->getTemplateCode();
        $content['request_method']   = $page->getRequestMethod();
        $content['created_at']       = $page->getCreatedAt()->format('U');
        $content['updated_at']       = $page->getUpdatedAt()->format('U');
        $content['slug']             = $page->getSlug();
        $content['parent_id']        = $page->getParent() ? $page->getParent()->getId() : null;
        $content['target_id']        = $page->getTarget() ? $page->getTarget()->getId() : null;
        $content['cssClass']         = $page->getCssClass();

        $content['blocks'] = array();
        foreach ($page->getBlocks() as $block) {
            if ($block->getParent()) { // ignore block with a parent => must be a child of a main
                continue;
            }

            $content['blocks'][] = $this->createBlocks($block);
        }

        $snapshot->setContent($content);

        foreach ($page->getTranslations() as $locale => $ptrans) {
            $content                     = array();
            $content['id']               = $page->getId();
            $content['name']             = $ptrans->getName();
            $content['javascript']       = $page->getJavascript();
            $content['stylesheet']       = $page->getStylesheet();
            $content['raw_headers']      = $page->getRawHeaders();
            $content['title']            = $ptrans->getTitle();
            $content['description']      = $ptrans->getDescription();
            $content['icon_id']          = $page->getIcon() ? $page->getIcon()->getId() : null;
            $content['meta_description'] = $ptrans->getMetaDescription();
            $content['meta_keyword']     = $ptrans->getMetaKeyword();
            $content['template_code']    = $page->getTemplateCode();
            $content['request_method']   = $page->getRequestMethod();
            $content['created_at']       = $page->getCreatedAt()->format('U');
            $content['updated_at']       = $page->getUpdatedAt()->format('U');
            $content['slug']             = $ptrans->getSlug();
            $content['parent_id']        = $page->getParent() ? $page->getParent()->getId() : null;
            $content['target_id']        = $page->getTarget() ? $page->getTarget()->getId() : null;
            $content['cssClass']         = $page->getCssClass();

            $content['blocks'] = array();
            foreach ($page->getBlocks() as $block) {
                if ($block->getParent()) { // ignore block with a parent => must be a child of a main
                    continue;
                }

                $content['blocks'][] = $this->createTranslatedBlocks($block, $locale);
            }

            $strans = new SnapshotTranslation();
            $strans->setObject($snapshot);
            $strans->setLocale($locale);
            $strans->setEnabled($ptrans->getEnabled());
            $strans->setName($ptrans->getName());
            $strans->setUrl($ptrans->getUrl());
            $strans->setContent($content);
            $snapshot->addTranslation($strans);
        }

        return $snapshot;
    }

    protected function createTranslatedBlocks(BlockInterface $block, $locale)
    {
        $content               = array();
        $content['id']         = $block->getId();
        $content['name']       = $block->getName();
        $content['enabled']    = isset($block->getTranslations()[$locale]) ? $block->getTranslations()[$locale]->getEnabled() : $block->getEnabled();
        $content['position']   = $block->getPosition();
        $content['settings']   = isset($block->getTranslations()[$locale]) ? $block->getTranslations()[$locale]->getSettings() : $block->getSettings();
        $content['type']       = $block->getType();
        $content['created_at'] = $block->getCreatedAt()->format('U');
        $content['updated_at'] = $block->getUpdatedAt()->format('U');
        $content['blocks']     = array();

        foreach ($block->getChildren() as $child) {
            $content['blocks'][] = $this->createTranslatedBlocks($child, $locale);
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function load(SnapshotInterface $snapshot)
    {
        $page = $this->pageManager->create();

        $page->setRouteName($snapshot->getRouteName());
        $page->setPageAlias($snapshot->getPageAlias());
        $page->setType($snapshot->getType());
        $page->setCustomUrl($snapshot->getUrl());
        $page->setUrl($snapshot->getUrl());
        $page->setPosition($snapshot->getPosition());
        $page->setDecorate($snapshot->getDecorate());
        $page->setSite($snapshot->getSite());
        $page->setEnabled($snapshot->getEnabled());

        $content = $this->fixPageContent($snapshot->getContent());

        $icon = $content['icon_id'] ? $this->registry->getManager()->getRepository('SymbioOrangeGateMediaBundle:Media')->findOneById($content['icon_id']) : null;

        $page->setId($content['id']);
        $page->setJavascript($content['javascript']);
        $page->setStylesheet($content['stylesheet']);
        $page->setRawHeaders($content['raw_headers']);
        $page->setTitle($content['title']);
        $page->setDescription($content['description']);
        $page->setIcon($icon);
        $page->setMetaDescription($content['meta_description']);
        $page->setMetaKeyword($content['meta_keyword']);
        $page->setName($content['name']);
        $page->setSlug($content['slug']);
        $page->setTemplateCode($content['template_code']);
        $page->setRequestMethod($content['request_method']);
        $page->setCssClass($content['cssClass']);

        $createdAt = new \DateTime;
        $createdAt->setTimestamp($content['created_at']);
        $page->setCreatedAt($createdAt);

        $updatedAt = new \DateTime;
        $updatedAt->setTimestamp($content['updated_at']);
        $page->setUpdatedAt($updatedAt);

        foreach ($snapshot->getTranslations() as $locale => $strans) {
            $ptrans = new PageTranslation();
            $ptrans->setObject($page);
            $ptrans->setLocale($locale);
            $ptrans->setEnabled($strans->getEnabled());
            $ptrans->setName($strans->getName());
            $ptrans->setUrl($strans->getUrl());

            $content = $strans->getContent();
            $ptrans->setObject($page);
            $ptrans->setLocale($locale);
            $ptrans->setTitle($content['title']);
            $ptrans->setDescription($content['description']);
            $ptrans->setMetaDescription($content['meta_description']);
            $ptrans->setMetaKeyword($content['meta_keyword']);
            $ptrans->setName($content['name']);
            $ptrans->setSlug($content['slug']);

            $page->addTranslation($ptrans);
        }

        return $page;
    }
}
