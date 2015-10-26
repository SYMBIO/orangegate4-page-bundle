<?php

namespace Symbio\OrangeGate\PageBundle\Consumer;

use Sonata\PageBundle\Model\PageManagerInterface;

use Sonata\NotificationBundle\Backend\BackendInterface;
use Sonata\NotificationBundle\Consumer\ConsumerInterface;
use Sonata\NotificationBundle\Consumer\ConsumerEvent;

/**
 * Consumer class to generate snapshots
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class CreateSnapshotsConsumer implements ConsumerInterface
{
    protected $asyncBackend;

    protected $runtimeBackend;

    protected $pageInterface;

    /**
     * @param BackendInterface     $asyncBackend
     * @param BackendInterface     $runtimeBackend
     * @param PageManagerInterface $pageManager
     */
    public function __construct(BackendInterface $asyncBackend, BackendInterface $runtimeBackend, PageManagerInterface $pageManager)
    {
        $this->asyncBackend   = $asyncBackend;
        $this->runtimeBackend = $runtimeBackend;
        $this->pageManager    = $pageManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ConsumerEvent $event)
    {
        $pages = $this->pageManager->findBy(array(
            'site' => $event->getMessage()->getValue('siteId'),
            'edited' => true,
        ));

        $backend = $event->getMessage()->getValue('mode') == 'async' ? $this->asyncBackend : $this->runtimeBackend;

        foreach ($pages as $page) {
            $backend->createAndPublish('sonata.page.create_snapshot', array(
                'pageId' => $page->getId()
            ));
        }
    }
}
