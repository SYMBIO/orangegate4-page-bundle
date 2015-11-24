<?php

namespace Symbio\OrangeGate\PageBundle\CmsManager;

use Sonata\BlockBundle\Model\BlockInterface;

use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Model\SnapshotManagerInterface;
use Sonata\PageBundle\Model\SnapshotPageProxy;
use Sonata\PageBundle\Model\TransformerInterface;
use Sonata\BlockBundle\Util\RecursiveBlockIterator;
use Sonata\PageBundle\Model\SiteInterface;
use Sonata\PageBundle\Exception\PageNotFoundException;
use Sonata\PageBundle\CmsManager\BaseCmsPageManager;

/**
 * The CmsSnapshotManager class is in charge of retrieving the correct page (cms page or action page)
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class CmsSnapshotManager extends BaseCmsPageManager
{
    protected $snapshotManager;

    protected $transformer;

    protected $pageReferences = array();

    protected $pages = array();

    /**
     * @param SnapshotManagerInterface $snapshotManager
     * @param TransformerInterface     $transformer
     */
    public function __construct(SnapshotManagerInterface $snapshotManager, TransformerInterface $transformer)
    {
        $this->snapshotManager = $snapshotManager;
        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public function getPage(SiteInterface $site = null, $page)
    {
        if (is_string($page) && substr($page, 0, 1) == '/') {
            $page = $this->getPageByUrl($site, $page);
        } elseif (is_string($page)) { // page is a slug, load the related page
            $page = $this->getPageByRouteName($site, $page);
        } elseif (is_numeric($page)) {
            $page = $this->getPageById($page);
        } elseif (!$page) { // get the current page
            $page = $this->getCurrentPage();
        }

        if (!$page instanceof PageInterface) {
            throw new PageNotFoundException('Unable to retrieve the snapshot');
        }

        return $page;
    }

    /**
     * {@inheritdoc}
     */
    public function getInternalRoute(SiteInterface $site, $pageName)
    {
        return $this->getPageByRouteName($site, sprintf('_page_internal_%s', $pageName));
    }

    /**
     * {@inheritdoc}
     */
    public function findContainer($code, PageInterface $page, BlockInterface $parentContainer = null)
    {
        $container = null;

        if ($parentContainer) {
            // parent container is set, nothing to find, don't need to loop across the
            // name to find the correct container (main template level)
            $container = $parentContainer;
        }

        // first level blocks are containers
        if (!$container && $page->getBlocks()) {
            foreach ($page->getBlocks() as $block) {
                if ($block->getSetting('code') == $code) {
                    $container = $block;
                    break;
                }
            }
        }

        return $container;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPageBy(SiteInterface $site = null, $fieldName, $value)
    {
        if ('id' == $fieldName) {
            $fieldName = 'pageId';
            $id = $value;
        } elseif ($site && isset($this->pageReferences[$site->getId()][$fieldName][$value])) {
            $id = $this->pageReferences[$site->getId()][$fieldName][$value];
        } else {
            $id = null;
        }

        if (null === $id || !$site || ($site && !isset($this->pages[$site->getId()][$id]))) {
            if ($fieldName === 'url') {
                $snapshot = $this->snapshotManager->findOneByUrl($site, $value);
            } else {
                $parameters = array($fieldName => $value);

                if ($site) {
                    $parameters['site'] = $site;
                }

                $snapshot = $this->snapshotManager->findEnableSnapshot($parameters);
            }

            if (!$snapshot) {
                throw new PageNotFoundException();
            }

            $page = new SnapshotPageProxy($this->snapshotManager, $this->transformer, $snapshot);

            $site = $page->getSite();

            $this->pages[$site->getId()][$id] = false;

            if ($page) {
                $this->loadBlocks($page);

                $id = $page->getId();

                if ($fieldName != 'id') {
                    $this->pageReferences[$site->getId()][$fieldName][$value] = $id;
                }

                $this->pages[$site->getId()][$id] = $page;
            }
        }

        return $this->pages[$site->getId()][$id];
    }

    /**
     * load the blocks of the $page
     *
     * @param \Sonata\PageBundle\Model\PageInterface $page
     */
    private function loadBlocks(PageInterface $page)
    {
        $i = new \RecursiveIteratorIterator(
            new RecursiveBlockIterator($page->getBlocks()),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($i as $block) {
            $this->blocks[$block->getId()] = $block;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlock($id)
    {
        if (isset($this->blocks[$id])) {
            return $this->blocks[$id];
        }

        return null;
    }
}
