<?php

namespace Symbio\OrangeGate\PageBundle\Admin;

use Doctrine\ORM\EntityRepository;
use Sonata\PageBundle\Admin\BlockAdmin as BaseAdmin;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Admin class for the Block model
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class BlockAdmin extends BaseAdmin
{
    protected $parentAssociationMapping = 'page';


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

    /**
     * @param \Symbio\OrangeGate\PageBundle\Entity\Block $object
     * @return mixed|void
     */
    public function preUpdate($object)
    {
        parent::preUpdate($object);

        if ($object->getPage()) {
            $object->setSite($object->getPage()->getSite());
        }

        $translations = $object->getTranslations();

        foreach ($translations as $trans) {
            $trans->setObject($object);
        }

        // because of current locale's translation being overwritten with base object data
        $object->setSettings($translations[$this->getRequest()->getLocale()]->getSettings());
        $object->setEnabled($translations[$this->getRequest()->getLocale()]->getEnabled());
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
                    $block->setSettings($resolver->resolve($block->getSettings() ?: array()));
                }
            } catch (InvalidOptionsException $e) {
                // @TODO : add a logging error or a flash message

            }

            $service->load($subject);
        }

        return $subject;
    }
}
