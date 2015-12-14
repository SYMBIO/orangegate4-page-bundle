<?php

namespace Symbio\OrangeGate\PageBundle\Admin;

use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\BlockBundle\Block\BaseBlockService;
use Sonata\PageBundle\Entity\BaseBlock;
use Sonata\PageBundle\Admin\SharedBlockAdmin as BaseBlockAdmin;
use Symbio\OrangeGate\PageBundle\Entity\SitePool;

/**
 * Admin class for shared Block model
 */
class SharedBlockAdmin extends BaseBlockAdmin
{
    protected $listModes = array(
        'list' => array(
            'class' => 'fa fa-list fa-fw',
        ),
    );

    /**
     * @var SitePool
     */
    protected $sitePool;

    public function setSitePool(SitePool $sitePool)
    {
        $this->sitePool = $sitePool;
    }

    /**
     * {@inheritdoc}
     */
    public function prePersist($object)
    {
        parent::prePersist($object);

        $translations = $object->getTranslations();

        foreach ($translations as $trans) {
            $trans->setObject($object);
        }
    }

    public function getNewInstance()
    {
        $object = parent::getNewInstance();
        $object->setSite($this->sitePool->getCurrentSite($this->getRequest()));

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate($object)
    {
        parent::preUpdate($object);

        if ($object->getPage()) {
            $site = $object->getPage()->getSite();
            $object->setSite($site);
        }

        $translations = $object->getTranslations();

        foreach ($translations as $trans) {
            $trans->setObject($object);
        }

        // because of current locale's translation being overwritten with base object data
        if (isset($site)) {
            $locale = $site->getDefaultLocale();
            if ($locale) {
                $object->setSettings($translations[$locale]->getSettings());
                $object->setEnabled($translations[$locale]->getEnabled());
            }
        }
    }
}
