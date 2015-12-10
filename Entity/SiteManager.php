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
        // remove trailing www. cause we automatically add it to test
        $host = preg_replace('/^www[.]/', '', $host);

        $result = $this->getEntityManager()->createQuery('
                SELECT s, lv, IF(lv.host = \'localhost\', 1, 0) as t
                FROM SymbioOrangeGatePageBundle:Site s
                INNER JOIN s.languageVersions lv
                WHERE
                  lv.host in (:wwwhost, :host, \'localhost\')
                  AND lv.enabled = 1
                  AND s.enabled = 1
                ORDER BY t,lv.isDefault ASC
            ')
            ->setParameter('host', $host)
            ->setParameter('wwwhost', 'www.'.$host)
            ->execute();

        $sites = array();
        foreach ($result as $r) {
            $sites[] = $r[0];
        }

        return $sites;
    }
}
