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
use Symfony\Component\Routing\Exception\RouteNotFoundException;
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
 * Admin definition for the Page class
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class PageAdmin extends BaseAdmin
{
    /**
     * @var PageManagerInterface
     */
    protected $pageManager;

    /**
     * @var SiteManagerInterface
     */
    protected $siteManager;

    /**
     * @var CacheManagerInterface
     */
    protected $cacheManager;

    public function configureRoutes(RouteCollection $routes)
    {
        $routes->add('compose', '{id}/compose', array(
            'id' => null,
        ));
        $routes->add('compose_container_show', 'compose/container/{id}', array(
            'id' => null,
        ));

        $routes->add('tree', 'tree');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('site')
            ->add('routeName')
            ->add('pageAlias')
            ->add('type')
            ->add('decorate')
            ->add('name')
            ->add('slug')
            ->add('customUrl')
            ->add('edited')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('hybrid', 'text', array('template' => 'SonataPageBundle:PageAdmin:field_hybrid.html.twig'))
            ->addIdentifier('name')
            ->add('type')
            ->add('pageAlias')
            ->add('site')
            ->add('decorate', null, array('editable' => true))
            ->add('edited', null, array('editable' => true))
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
        // define group zoning
        $formMapper
             ->with($this->trans('form_page.group_basic_label'), array('class' => 'col-md-6'))->end()
             ->with($this->trans('form_page.group_settings_label'), array('class' => 'col-md-6'))->end()
             ->with($this->trans('form_page.group_advanced_label'), array('class' => 'col-md-6'))->end()
        ;

        if (!$this->getSubject() || (!$this->getSubject()->isInternal() && !$this->getSubject()->isError())) {
            $formMapper
                ->with($this->trans('form_page.group_basic_label'))
                    ->add('translations', 'orangegate_translations', array(
                        'label' => false,
                        'locales' => array('cs', 'en', 'de'),
                        'fields' => array(
                                'enabled' => array(
                                    'field_type' => 'checkbox',
                                    'required' => false,
                                ),
                                'name' => array(
                                    'field_type' => 'text',
                                    'required' => false,
                                ),
                                'description' => array(
                                    'field_type' => 'textarea',
                                    'required' => false,
                                ),
                                'slug' => array(
                                    'field_type' => 'text',
                                    'required' => false,
                                ),
                                'url' => array(
                                    'field_type' => 'text',
                                    'attr' => array('readonly' => 'readonly'),
                                ),
                                'title' => array(
                                    'field_type' => 'text',
                                    'required' => false,
                                ),
                                'metaKeyword' => array(
                                    'field_type' => 'textarea',
                                    'required' => false,
                                ),
                                'metaDescription' => array(
                                    'field_type' => 'textarea',
                                    'required' => false,
                                ),
                            ),
                    ))
                ->end()
            ;
        }

        if ($this->hasSubject() && !$this->getSubject()->getId() && !$this->getSubject()->getSite()) {
            $formMapper
                ->with($this->trans('form_page.group_settings_label'))
                    ->add('site', null, array('required' => true, 'read_only' => true))
                ->end();
        }

        $formMapper
            ->with($this->trans('form_page.group_settings_label'))
                ->add('templateCode', 'sonata_page_template', array('required' => true))
                ->add('parent', 'orangegate_page_selector', array(
                    'page'          => $this->getSubject() ?: null,
                    'site'          => $this->getSubject() ? $this->getSubject()->getSite() : null,
                    'model_manager' => $this->getModelManager(),
                    'class'         => $this->getClass(),
                    'required'      => !$this->isGranted('EDIT'),
                    //'filter_choice' => array('root' => $this->isGranted('EDIT') ? false : $this->getSubject->getParent()),
                ), array(
                    'link_parameters' => array(
                        'siteId' => $this->getSubject() ? $this->getSubject()->getSite()->getId() : null
                    )
                ))
                ->add('icon', 'sonata_type_model_list', array('required' => false), array(
                    'placeholder' => 'No image selected',
                    'link_parameters' => array(
                        'context' => 'lovochemie',
                        'provider' => 'sonata.media.provider.image',
                        'category' => 108,
                        'hide_context' => true,
                    )
                ))
                ->add('position')
            ->end();
/*
        if ($this->hasSubject() && !$this->getSubject()->isInternal()) {
            $formMapper
                ->with($this->trans('form_page.group_settings_label'))
                    ->add('type', 'sonata_page_type_choice', array('required' => false))
                ->end()
            ;
        }

        if (!$this->getSubject() || !$this->getSubject()->isDynamic()) {
            $formMapper
                ->with($this->trans('form_page.group_settings_label'))
                    ->add('target', 'sonata_page_selector', array(
                        'page'          => $this->getSubject() ?: null,
                        'site'          => $this->getSubject() ? $this->getSubject()->getSite() : null,
                        'model_manager' => $this->getModelManager(),
                        'class'         => $this->getClass(),
                        'filter_choice' => array('request_method' => 'all'),
                        'required'      => false
                    ), array(
                        'link_parameters' => array(
                            'siteId' => $this->getSubject() ? $this->getSubject()->getSite()->getId() : null
                        )
                    ))
                ->end()
            ;
        }
*/

        if ($this->hasSubject() && !$this->getSubject()->isCms()) {
            $formMapper
                ->with($this->trans('form_page.group_advanced_label'), array('collapsed' => false))
                    ->add('decorate', null,  array('required' => false))
                ->end();
        }

        $formMapper
            ->with($this->trans('form_page.group_advanced_label'), array('collapsed' => false))
                ->add('pageAlias', null, array('required' => false))
                ->add('javascript', null,  array('required' => false))
                ->add('stylesheet', null, array('required' => false))
                ->add('rawHeaders', null, array('required' => false))
            ->end()
        ;

        $formMapper->setHelps(array(
            'name' => $this->trans('help_page_name')
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function configureSideMenu(MenuItemInterface $menu, $action, AdminInterface $childAdmin = null)
    {
        if (!$childAdmin && !in_array($action, array('edit'))) {
            return;
        }

        $admin = $this->isChild() ? $this->getParent() : $this;

        $id = $admin->getRequest()->get('id');

        $current_route = $admin->getRequest()->get('_route');

        $editNode = $menu->addChild(
            $this->trans('sidemenu.link_edit_page'),
            array('uri' => $admin->generateUrl('edit', array('id' => $id)), 'attributes' => array('class' => $admin->getRequest()->get('_route') == 'admin_orangegate_page_page_edit' ? 'active' : ''))
        );

        $menu->addChild(
            $this->trans('sidemenu.link_compose_page'),
            array('uri' => $admin->generateUrl('compose', array('id' => $id)), 'attributes' => array('class' => $admin->getRequest()->get('_route') == 'admin_orangegate_page_page_compose' ? 'active' : ''))
        );

        $menu->addChild(
            $this->trans('sidemenu.link_list_blocks'),
            array('uri' => $admin->getChild('sonata.page.admin.block')->generateUrl('list', array('id' => $id)), 'attributes' => array('class' => $admin->getRequest()->get('_route') == 'admin_orangegate_page_page_block_list' ? 'active' : ''))
        );

        if ($this->securityHandler->isGranted($this->getChild('sonata.page.admin.snapshot'), 'EDIT', $this->getChild('sonata.page.admin.snapshot'))) {
            $menu->addChild(
                $this->trans('sidemenu.link_list_snapshots'),
                array('uri' => $admin->generateUrl('sonata.page.admin.snapshot.list', array('id' => $id)), 'attributes' => array('class' => $admin->getRequest()->get('_route') == 'admin_orangegate_page_page_snapshot_list' ? 'active' : ''))
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate($object)
    {
        $object->setEdited(true);

        $this->pageManager->fixUrl($object);
    }

    /**
     * {@inheritdoc}
     */
    public function postUpdate($object)
    {
        if ($this->cacheManager) {
            $this->cacheManager->invalidate(array(
                'page_id' => $object->getId()
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prePersist($object)
    {
        $object->setEdited(true);

        foreach ($object->getTranslations() as $t) {
            $t->setObject($object);
        }

        $this->pageManager->fixUrl($object);
    }

    /**
     * @param \Sonata\PageBundle\Model\PageManagerInterface $pageManager
     */
    public function setPageManager(PageManagerInterface $pageManager)
    {
        $this->pageManager = $pageManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewInstance()
    {
        $instance = parent::getNewInstance();

        if (!$this->hasRequest()) {
            return $instance;
        }

        if ($site = $this->getSite()) {
            $instance->setSite($site);
        }

        if ($site && $this->getRequest()->get('url')) {
            $slugs = explode('/', $this->getRequest()->get('url'));
            $slug  = array_pop($slugs);

            try {
                $parent = $this->pageManager->getPageByUrl($site, implode('/', $slugs));
            } catch (PageNotFoundException $e) {
                try {
                    $parent = $this->pageManager->getPageByUrl($site, '/');
                } catch (PageNotFoundException $e) {
                    throw new InternalErrorException('Unable to find the root url, please create a route with url = /');
                }
            }

            $instance->setSlug(urldecode($slug));
            $instance->setParent($parent ?: null);
            $instance->setName(urldecode($slug));
        }

        return $instance;
    }

    /**
     * @return SiteInterface
     *
     * @throws \RuntimeException
     */
    public function getSite()
    {
        if (!$this->hasRequest()) {
            return false;
        }

        $siteId = null;

        if ($this->getRequest()->getMethod() == 'POST') {
            $values = $this->getRequest()->get($this->getUniqid());
            $siteId = isset($values['site']) ? $values['site'] : null;
        }

        $siteId = (null !== $siteId) ? $siteId : $this->getRequest()->get('siteId');

        if ($siteId) {
            $site = $this->siteManager->findOneBy(array('id' => $siteId));

            if (!$site) {
                throw new \RuntimeException('Unable to find the site with id=' . $this->getRequest()->get('siteId'));
            }

            return $site;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchActions()
    {
        $actions = parent::getBatchActions();

        $actions['snapshot'] = array(
            'label'            => $this->trans('create_snapshot'),
            'ask_confirmation' => true
        );

        return $actions;
    }

    /**
     * @param \Sonata\PageBundle\Model\SiteManagerInterface $siteManager
     */
    public function setSiteManager(SiteManagerInterface $siteManager)
    {
        $this->siteManager = $siteManager;
    }

    /**
     * @return array
     */
    public function getSites()
    {
        return $this->siteManager->findBy(array());
    }

    /**
     * @param \Sonata\Cache\CacheManagerInterface $cacheManager
     */
    public function setCacheManager(CacheManagerInterface $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    public function getLanguages()
    {
        return array('cs', 'en', 'de');
    }
}
