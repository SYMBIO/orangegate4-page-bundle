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
                        $t->setSettings($resolver->resolve($t->getSettings()));
                    }
                } else {
                    $block->setSettings($resolver->resolve($block->getSettings()));
                }
            } catch (InvalidOptionsException $e) {
                // @TODO : add a logging error or a flash message

            }

            $service->load($subject);
        }

        return $subject;
    }
}
