<?php

namespace Symbio\OrangeGate\PageBundle\Entity;

use Sonata\PageBundle\Model\SiteManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class SitePool
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var SiteManagerInterface
     */
    protected $siteManager;

    private $sites = array();

    const SESSION_NAME = 'orangegate_site';

    public function __construct(SiteManagerInterface $siteManager, Session $session)
    {
        $this->siteManager = $siteManager;
        $this->session = $session;
    }

    public function getSites()
    {
        if (!$this->sites) {
            $this->sites = $this->siteManager->findBy(array(), array('name' => 'ASC'));
        }

        return $this->sites;
    }

    public function getCurrentSite(Request $request)
    {
        $currentSite = null;
        $siteId = $request->get('site');

        if (!$siteId && $this->session->has(self::SESSION_NAME)) {
            $currentSiteId = $this->session->get(self::SESSION_NAME);
            $currentSite = $this->siteManager->find($currentSiteId);
            if (!$currentSite) {
                $currentSite = $this->getSites()[0];
            }
        } else {
            foreach ($this->getSites() as $site) {
                if ($siteId && $site->getId() == $siteId) {
                    $currentSite = $site;
                } elseif (!$siteId && $site->getIsDefault()) {
                    $currentSite = $site;
                }
            }

            if (!$currentSite && count($this->sites) > 0) {
                $currentSite = $this->sites[0];
            }
        }

        if ($currentSite) {
            $this->session->set(self::SESSION_NAME, $currentSite->getId());
        }

        return $currentSite;
    }
}