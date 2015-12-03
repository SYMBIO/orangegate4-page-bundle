<?php

namespace Symbio\OrangeGate\PageBundle\Admin;

use Doctrine\ORM\EntityRepository;
use Gedmo\Translatable\TranslatableListener;
use Sonata\PageBundle\Admin\BlockAdmin as BaseAdmin;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symbio\OrangeGate\PageBundle\Entity\BlockTranslation;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Admin class for the Block model
 */
class BlockAdmin extends BaseAdmin
{
    protected $parentAssociationMapping = 'page';

    /**
     * @var TranslatableListener
     */
    protected $translatableListener;

    protected $locales = array();

    /**
     * @param string $code
     * @param string $class
     * @param string $baseControllerName
     * @param TranslatableListener $translatableListener
     */
    public function __construct($code, $class, $baseControllerName, TranslatableListener $translatableListener)
    {
        $this->translatableListener = $translatableListener;

        parent::__construct($code, $class, $baseControllerName);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollection $collection)
    {
        parent::configureRoutes($collection);

        $collection->add('savePosition', 'save-position');
        $collection->add('view', $this->getRouterIdParameter().'/view');
        $collection->add('switchParent', 'switch-parent');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $block = $this->getSubject();
        if ($block->getId() === null) { // new block
            $service = $this->blockManager->getService($this->request->get('type'));
            $block->setName($service->getName());
        }

        $this->translatableListener->setTranslatableLocale($block->getSite()->getDefaultLocale());
        $this->translatableListener->setFallbackLocales($block->getSite()->getLocales());

        return parent::configureFormFields($formMapper);
    }

    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('type')
            ->add('name')
            ->add('settings')
            ->add('enabled')
            ->add('updatedAt')
            ->add('position')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function prePersist($object)
    {
        parent::prePersist($object);

        if ($object->getPage()) {
            $object->setSite($object->getPage()->getSite());
        }

        $translations = $object->getTranslations();

        foreach ($translations as $trans) {
            $trans->setObject($object);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create($object)
    {
        $this->prePersist($object);
        foreach ($this->extensions as $extension) {
            $extension->prePersist($this, $object);
        }

        $result = $this->getModelManager()->create($object);
        // BC compatibility
        if (null !== $result) {
            $object = $result;
        }

        $this->postPersist($object);
        foreach ($this->extensions as $extension) {
            $extension->postPersist($this, $object);
        }

        $this->createObjectSecurity($object);

        return $object;
    }

    public function getNewInstance()
    {
        $object = parent::getNewInstance();

        if ($this->isChild() && $this->getParentAssociationMapping()) {
            $parent = $this->getParent()->getObject($this->request->get($this->getParent()->getIdParameter()));

            if ($parent) {
                $object->setSite($parent->getSite());
            }
        }

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

    /**
     * {@inheritdoc}
     */
    public function getObject($id)
    {
        $subject = $this->getModelManager()->find($this->getClass(), $id);
        foreach ($this->getExtensions() as $extension) {
            $extension->alterObject($this, $subject);
        }

        if ($subject) {
            $service = $this->blockManager->get($subject);

            $resolver = new OptionsResolver();
            $service->setDefaultSettings($resolver);

            try {
                if ($subject->getTranslations() && $subject->getTranslations()->count() > 0) {
                    foreach ($subject->getTranslations() as $t) {
                        $t->setSettings($resolver->resolve($t->getSettings() ?: array()));
                    }
                } else {
                    $subject->setSettings($resolver->resolve($subject->getSettings() ?: array()));
                }
            } catch (InvalidOptionsException $e) {
                // @TODO : add a logging error or a flash message

            }

            $service->load($subject);
        }

        return $subject;
    }
}
