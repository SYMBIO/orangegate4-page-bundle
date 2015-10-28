<?php

namespace Symbio\OrangeGate\PageBundle\Entity;

use Sonata\CoreBundle\Model\BaseEntityManager;
use Sonata\PageBundle\Model\SiteManagerInterface;
use Sonata\PageBundle\Entity\SiteManager as BaseSiteManager;

/**
 * This class manages SiteInterface persistency with the Doctrine ORM
 */
class SiteManager extends BaseSiteManager
{
    /**
     * {@inheritdoc}
     */
    public function findByHost($host)
    {
        return $this->getEntityManager()->createQuery('
                SELECT s, lv
                FROM SymbioOrangeGatePageBundle:Site s
                INNER JOIN s.languageVersions lv
                WHERE
                  (lv.host = \'localhost\' OR lv.host = :host OR lv.host = :wwwhost)
                  AND lv.enabled = 1
                  AND s.enabled = 1
                ORDER BY lv.isDefault ASC
            ')
            ->setParameter('host', $host)
            ->setParameter('wwwhost', 'www.'.$host)
            ->execute();
    }
}
