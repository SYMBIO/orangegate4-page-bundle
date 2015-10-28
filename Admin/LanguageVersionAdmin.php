<?php

namespace Symbio\OrangeGate\PageBundle\Admin;

use Symbio\OrangeGate\AdminBundle\Admin\Admin as BaseAdmin;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\PageBundle\Route\RoutePageGenerator;

class LanguageVersionAdmin extends BaseAdmin
{
    /**
     * @var RoutePageGenerator
     */
    protected $routePageGenerator;
    protected $translationDomain = 'SonataPageBundle';

    /**
     * Constructor
     *
     * @param string             $code               A Sonata admin code
     * @param string             $class              A Sonata admin class name
     * @param string             $baseControllerName A Sonata admin base controller name
     * @param RoutePageGenerator $routePageGenerator Sonata route page generator service
     */
    public function __construct($code, $class, $baseControllerName, RoutePageGenerator $routePageGenerator)
    {
        $this->routePageGenerator = $routePageGenerator;

        parent::__construct($code, $class, $baseControllerName);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('name')
            ->add('enabled')
            ->add('title')
            ->add('metaDescription')
            ->add('metaKeywords')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name')
            ->add('enabled', null, array('editable' => true))
            ->add('create_snapshots', 'string', array('template' => 'SonataPageBundle:SiteAdmin:list_create_snapshots.html.twig'))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with($this->trans('form_site.label_general'), array('class' => 'col-md-6'))
                ->add('name')
                ->add('enabled', null, array('required' => false))
                ->add('locale', 'locale', array('required' => false))
                ->add('isDefault', null, array('required' => false))
                ->add('host')
                ->add('relativePath', null, array('required' => false))
                ->add('title', null, array('required' => false))
                ->add('metaDescription', 'textarea', array('required' => false, /*'label' => 'form.label_meta_description'*/))
                ->add('metaKeywords', 'textarea', array('required' => false))
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('snapshots', $this->getRouterIdParameter().'/snapshots');
    }
}
