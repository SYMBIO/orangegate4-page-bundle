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
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

use Sonata\PageBundle\Route\RoutePageGenerator;
use Symbio\OrangeGate\PageBundle\Entity\LanguageVersion;
use Symbio\OrangeGate\PageBundle\Entity\Site;

/**
 * Admin definition for the Site class
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class SiteAdmin extends BaseAdmin
{
    /**
     * @var RoutePageGenerator
     */
    protected $routePageGenerator;

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
            ->with($this->trans('form_site.label_general'))
                ->add('enabled', null, array('required' => false))
                ->add('name')
                ->add('favicon', 'sonata_type_model_list', array('required' => false), array(
                    'placeholder' => 'No image selected',
                    'link_parameters' => array(
                        'context' => 'default',
                        'category' => 82,
                        'provider' => 'sonata.media.provider.image'
                    )
                ))
            ->end()
            ->with($this->trans('form_site.label_languages'))
                ->add('languageVersions', 'sonata_type_collection', array(
                        'type_options' => array('delete' => true),
                        'label' => ' ',
                        'required' => false,
                    ), array(
                        'edit' => 'inline',
                        'inline' => 'blocks',
                    ))
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

    /**
     * {@inheritdoc}
     */
    public function postPersist($object)
    {
        $this->routePageGenerator->update($object);
    }

    public function prePersist($object)
    {
        foreach ($object->getLanguageVersions() as $lv) {
            $lv->setSite($object);
        }
    }

    public function preUpdate($object)
    {
        foreach ($object->getLanguageVersions() as $lv) {
            $lv->setSite($object);
        }
    }

    public function getNewInstance()
    {
        /**
         * @var Site $object
         */
        $object = parent::getNewInstance();
        $object->setEnabled(true);

        $lv = new LanguageVersion();
        $lv->setEnabled(true);
        $lv->setName('Default');
        $lv->setHost('localhost');
        $lv->setRelativePath('');
        $lv->setLocale('cs');
        $lv->setIsDefault(true);
        $object->addLanguageVersion($lv);

        return $object;
    }

}
