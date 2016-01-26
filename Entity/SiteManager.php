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
     * Finds sites for given hostname. Site with localhost hostname accepts always. Also accepts domains without TLD,
     * ie. it accepts hostname "agrofert" for site with hostname www.agrofert.cz.
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
                  (lv.host IN (:wwwhost, :host, \'localhost\') OR lv.host LIKE :host_plus_tld OR lv.host LIKE :www_host_plus_tld)
                  AND lv.enabled = 1
                  AND s.enabled = 1
                ORDER BY t,lv.isDefault ASC
            ')
            ->setParameter('host', $host)
            ->setParameter('wwwhost', 'www.'.$host)
            ->setParameter('host_plus_tld', $host.'.%')
            ->setParameter('www_host_plus_tld', 'www.'.$host.'.%')
            ->execute();

        $sites = array();
        foreach ($result as $r) {
            $sites[] = $r[0];
        }

        return $sites;
    }
}
