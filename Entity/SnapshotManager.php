<?php

namespace Symbio\OrangeGate\PageBundle\Entity;

use Doctrine\Common\Persistence\ManagerRegistry;
use Sonata\PageBundle\Entity\SnapshotManager as ParentManager;
use Sonata\PageBundle\Exception\PageNotFoundException;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Model\SiteInterface;
use Sonata\PageBundle\Model\SnapshotManagerInterface;
use Sonata\PageBundle\Model\SnapshotPageProxy;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * This class manages SnapshotInterface persistency with the Doctrine ORM
 */
class SnapshotManager extends ParentManager implements SnapshotManagerInterface
{

    private $snapshots = array();

    /**
     * {@inheritdoc}
     */
    public function loadSnapshots(SiteInterface $site)
    {
        $now = new \Datetime;

        $query = $this->getEntityManager()
            ->createQuery(sprintf('
              SELECT s,p
              FROM %s s
              INNER JOIN s.page p
              INDEX BY s.pageId
              WHERE
                s.enabled = 1
                  AND
                s.site = %d
                  AND
                s.publicationDateStart <= \'%s\'
                  AND
                ( s.publicationDateEnd IS NULL OR s.publicationDateEnd >= \'%s\' )
              ORDER BY s.position ASC
              ', $this->class, $site->getId(), $now->format('Y-m-d H:i:s'), $now->format('Y-m-d H:i:s')));

        $query->setHint(\Gedmo\Translatable\TranslatableListener::HINT_INNER_JOIN, true);
        $query->setHint(
            \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
        );

        $query->setHint(
            \Gedmo\Translatable\TranslatableListener::HINT_TRANSLATABLE_LOCALE,
            $site->getLocale()
        );

        $this->snapshots = $query->execute();
    }

    public function findOneByUrl($url)
    {
        foreach ($this->snapshots as $snapshot) {
            if ($this->matches($snapshot, $url)) {
                return $snapshot;
            }
        }

        return null;
    }

    public function findOneByRouteName($routeName)
    {
        foreach ($this->snapshots as $snapshot) {
            if ($snapshot->getRouteName() === $routeName) {
                return $snapshot;
            }
        }

        return null;
    }

    public function findOneByPageId($id)
    {
        if (isset($this->snapshots[$id])) {
            return $this->snapshots[$id];
        }

        return null;
    }

    /**
     * Test the $page if it matches the $url
     *
     * @param Page $page
     * @param $url
     * @return bool
     */
    protected function matches(Snapshot $snapshot, $url)
    {
        $purl = $snapshot->getUrl();
        $pattern = '#^' . $purl . '$#';
        preg_match_all('/{[a-z]+}/', $purl, $matches);

        $tokens = $matches[0];

        foreach ($tokens as $token) {
            $pattern = preg_replace('/' . $token . '/', '(.+)', $pattern);
        }

        if (preg_match($pattern, $url, $matches)) {
            // remove brackets from tokens
            $tokens = array_map(function ($a) {
                return substr($a, 1, -1);
            }, $tokens);

            // remove first (whole) match
            array_shift($matches);

            $snapshot->getPage()->parameters = array_combine($tokens, $matches);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function findEnableSnapshot(array $criteria)
    {
        // if we don't know the site, we've to find it by page/snapshot
        if (!isset($criteria['site']) && isset($criteria['pageId']) && !$this->snapshots) {
            $site_id = $this->getEntityManager()->createQuery(sprintf("SELECT s.site.id FROM %s WHERE s.page_id = %s", $this->getClass(), $criteria['pageId']))->getSingleScalarResult();
            var_dump($site_id);exit;
            if ($snapshot) {
                $criteria['site'] = $snapshot->getSite();
            } else {
                return null;
            }
        }

        if (!$this->snapshots) {
            $this->loadSnapshots($criteria['site']);
        }

        if (isset($criteria['pageId'])) {
            return $this->findOneByPageId($criteria['pageId']);
        } elseif (isset($criteria['url'])) {
            return $this->findOneByUrl($criteria['url']);
        } elseif (isset($criteria['routeName'])) {
            return $this->findOneByRouteName($criteria['routeName']);
        } else {
            return null;
        }
    }
}
