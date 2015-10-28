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

        return $this->getEntityManager()->createQuery('
                SELECT s, lv
                FROM SymbioOrangeGatePageBundle:Site s
                INNER JOIN s.languageVersions lv
                WHERE
                  lv.host in (:wwwhost, :host, \'localhost\')
                  AND lv.enabled = 1
                  AND s.enabled = 1
                ORDER BY lv.isDefault ASC
            ')
            ->setParameter('host', $host)
            ->setParameter('wwwhost', 'www.'.$host)
            ->execute();
    }
}
