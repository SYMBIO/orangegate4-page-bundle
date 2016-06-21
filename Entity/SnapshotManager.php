<?php

namespace Symbio\OrangeGate\PageBundle\Entity;

use Doctrine\Common\Persistence\ManagerRegistry;
use Sonata\PageBundle\Entity\SnapshotManager as ParentManager;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Model\SiteInterface;
use Sonata\PageBundle\Model\SnapshotManagerInterface;
use Sonata\PageBundle\Model\SnapshotPageProxy;

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
              SELECT s FROM %s s
              INNER JOIN s.page p
              INDEX BY s.id
              WHERE s.site = %d AND s.publicationDateStart <= \'%s\' AND ( s.publicationDateEnd IS NULL OR s.publicationDateEnd >= \'%s\' )
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

        $snapshots = $query->execute();

        foreach ($snapshots as $snapshot) {
            $parent = $snapshot->getParent();

            $snapshot->disableChildrenLazyLoading();
            if (!$parent) {
                continue;
            }

            $snapshots[$parent->getId()]->disableChildrenLazyLoading();
        }

        return $snapshots;
    }

    public function findOneByUrl($site, $url)
    {
        $site_id = $site->getId();

        if (!isset($this->snapshots[$site_id])) {
            $this->snapshots[$site_id] = $this->loadSnapshots($site);
        }

        foreach ($this->snapshots[$site_id] as $snapshot) {

            if ($this->matches($snapshot, $url)) {
                return $snapshot;
            }
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
        $date = new \Datetime();
        $parameters = array(
            'publicationDateStart' => $date,
            'publicationDateEnd' => $date,
        );

        $qb = $this->getRepository()
            ->createQueryBuilder('s')
            ->andWhere('s.publicationDateStart <= :publicationDateStart AND ( s.publicationDateEnd IS NULL OR s.publicationDateEnd >= :publicationDateEnd )');

        if (isset($criteria['site'])) {
            $qb->andWhere('s.site = :site');
            $parameters['site'] = $criteria['site'];
        } else {
            $qb->innerJoin('s.translations', 't');
        }

        if (isset($criteria['pageId'])) {
            $qb->andWhere('s.page = :page');
            $parameters['page'] = $criteria['pageId'];
        } elseif (isset($criteria['url'])) {
            $qb->andWhere('s.url = :url');
            $parameters['url'] = $criteria['url'];
        } elseif (isset($criteria['routeName'])) {
            $qb->andWhere('s.routeName = :routeName');
            $parameters['routeName'] = $criteria['routeName'];
        } elseif (isset($criteria['pageAlias'])) {
            $qb->andWhere('s.pageAlias = :pageAlias');
            $parameters['pageAlias'] = $criteria['pageAlias'];
        } elseif (isset($criteria['name'])) {
            $qb->andWhere('s.name = :name');
            $parameters['name'] = $criteria['name'];
        } else {
            throw new \RuntimeException('please provide a `pageId`, `url`, `routeName` or `name` as criteria key');
        }

        $qb->setMaxResults(1);
        $qb->setParameters($parameters);

        $query = $qb->getQuery();

        if (isset($criteria['site'])) {
            $query->setHint(
                \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
                'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
            );
            $query->setHint(
                \Gedmo\Translatable\TranslatableListener::HINT_TRANSLATABLE_LOCALE,
                $criteria['site']->getLocale()
            );
        }


        return $query->getOneOrNullResult();
    }
}
