<?php

namespace Symbio\OrangeGate\PageBundle\Consumer;

use Sonata\PageBundle\Model\SnapshotManagerInterface;
use Sonata\PageBundle\Model\PageManagerInterface;
use Sonata\NotificationBundle\Consumer\ConsumerInterface;
use Sonata\NotificationBundle\Consumer\ConsumerEvent;
use Sonata\PageBundle\Model\TransformerInterface;

/**
 * Consumer class to generate a snapshot
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class CreateSnapshotConsumer implements ConsumerInterface
{
    protected $snapshotManager;

    protected $pageManager;

    protected $transformer;

    /**
     * @param SnapshotManagerInterface $snapshotManager
     * @param PageManagerInterface     $pageManager
     * @param TransformerInterface     $transformer
     */
    public function __construct(SnapshotManagerInterface $snapshotManager, PageManagerInterface $pageManager, TransformerInterface $transformer)
    {
        $this->snapshotManager = $snapshotManager;
        $this->pageManager     = $pageManager;
        $this->transformer     = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ConsumerEvent $event)
    {
        $pageId = $event->getMessage()->getValue('pageId');

        $page = $this->pageManager->findOneBy(array('id' => $pageId));

        if (!$page || $page->getEdited() === false) {
            return;
        }

        // start a transaction
        $this->snapshotManager->getConnection()->beginTransaction();

        // creating snapshot
        $snapshot = $this->transformer->create($page);

        // update the page status
        $page->setEdited(false);
        $this->pageManager->save($page);

        // save the snapshot
        $this->snapshotManager->save($snapshot);
        $this->snapshotManager->enableSnapshots(array($snapshot));

        // commit the changes
        $this->snapshotManager->getConnection()->commit();
    }
}
