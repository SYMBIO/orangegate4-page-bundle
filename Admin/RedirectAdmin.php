<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symbio\OrangeGate\PageBundle\Admin;

use Symbio\OrangeGate\AdminBundle\Admin\Admin as BaseAdmin;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

use Sonata\PageBundle\Exception\PageNotFoundException;
use Sonata\PageBundle\Exception\InternalErrorException;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Model\PageManagerInterface;
use Sonata\PageBundle\Model\SiteManagerInterface;

use Sonata\Cache\CacheManagerInterface;

use Knp\Menu\ItemInterface as MenuItemInterface;

/**
 * Admin definition for the Redirect class
 */
class RedirectAdmin extends BaseAdmin
{
    /**
     * @var SiteManagerInterface
     */
    protected $siteManager;

    /**
     * {@inheritdoc}
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('enabled')
            ->add('sourceUrl')
            ->add('destinationUrl')
            ->add('type')
            ->add('note')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('enabled', null, array('editable' => true))
            ->addIdentifier('sourceUrl')
            ->addIdentifier('destinationUrl')
            ->addIdentifier('type')
            ->addIdentifier('note')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        // get site
        $site = null;
        if ($this->getSubject()) {
            $site = $this->getSubject()->getSite();
        }

        if ($this->isGranted('ADMIN')) {
            $formMapper
                ->add('site', null, array('required' => true, 'read_only' => true))
                ->end();
        }

        $formMapper
            ->add('enabled')
            ->add('type', 'choice' , ['choices' => [301=>301,302=>302]])
            ->add('sourceUrl')
            ->add('destinationUrl')
            ->add('note')
            ->add('position')
            ->end();
    }
}
