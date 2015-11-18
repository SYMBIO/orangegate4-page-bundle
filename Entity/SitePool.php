<?php

namespace Symbio\OrangeGate\PageBundle\Entity;

use Sonata\PageBundle\Model\SiteManagerInterface;
use Symbio\OrangeGate\PageBundle\Admin\SiteAdmin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

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

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var SiteAdmin
     */
    protected $siteAdmin;

    private $sites = array();

    const SESSION_NAME = 'orangegate_site';

    public function __construct(SiteManagerInterface $siteManager, Session $session, AuthorizationCheckerInterface $authorizationChecker, SiteAdmin $siteAdmin)
    {
        $this->siteManager = $siteManager;
        $this->session = $session;
        $this->authorizationChecker = $authorizationChecker;
        $this->siteAdmin = $siteAdmin;
    }

    public function getSites()
    {
        if (!$this->sites) {
            $this->sites = $this->siteManager->findBy(array(), array('name' => 'ASC'));
        }

        if (!$this->siteAdmin->isGranted('LIST')) {
            $this->sites = array_values(array_filter($this->sites, function($site) {
                return $this->authorizationChecker->isGranted('VIEW', $site);
            }));
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
                $sites = $this->getSites();
                if (count($sites) > 0) {
                    $currentSite = $this->getSites()[0];
                }
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