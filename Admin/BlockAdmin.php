<?php

namespace Symbio\OrangeGate\PageBundle\Admin;

use Doctrine\ORM\EntityRepository;
use Sonata\PageBundle\Admin\BaseBlockAdmin as BaseAdmin;
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
    protected function configureFormFields(FormMapper $formMapper)
    {
        $block = $this->getSubject();

        $page = false;

        if ($this->getParent()) {
            $page = $this->getParent()->getSubject();

            if (!$page instanceof PageInterface) {
                throw new \RuntimeException('The BlockAdmin must be attached to a parent PageAdmin');
            }

            if ($block->getId() === null) { // new block
                $block->setType($this->request->get('type'));
                $block->setPage($page);
            }

            if ($block->getPage()->getId() != $page->getId()) {
                throw new \RuntimeException('The page reference on BlockAdmin and parent admin are not the same');
            }
        }

        $isContainerRoot = $block && in_array($block->getType(), array('sonata.page.block.container', 'sonata.block.service.container')) && !$this->hasParentFieldDescription();
        $isStandardBlock = $block && !in_array($block->getType(), array('sonata.page.block.container', 'sonata.block.service.container')) && !$this->hasParentFieldDescription();

        if ($isContainerRoot || $isStandardBlock) {
            $service = $this->blockManager->get($block);

            $containerBlockTypes = $this->containerBlockTypes;

            //$formMapper->with($this->trans('form.field_group_options'));

            // need to investigate on this case where $page == null ... this should not be possible
            if ($isStandardBlock && $page && !empty($containerBlockTypes)) {
                $formMapper->add('parent', 'entity', array(
                    'class' => $this->getClass(),
                    'query_builder' => function(EntityRepository $repository) use ($page, $containerBlockTypes) {
                        return $repository->createQueryBuilder('a')
                            ->andWhere('a.page = :page AND a.type IN (:types)')
                            ->setParameters(array(
                                'page'  => $page,
                                'types' => $containerBlockTypes,
                            ));
                    }
                ),array(
                    'admin_code' => $this->getCode()
                ));
            }

            if ($isStandardBlock) {
                $formMapper->add('position', 'integer');
            }

            //$formMapper->add('name');

            if ($block->getId() > 0) {
                $service->buildEditForm($formMapper, $block);
            } else {
                $service->buildCreateForm($formMapper, $block);
            }

        } else {

            //$formMapper->with($this->trans('form.field_group_general'));

            // add name on all forms
            $formMapper->add('name');

            $formMapper
                ->add('type', 'sonata_block_service_choice', array(
                    'context' => 'sonata_page_bundle'
                ))
                //->add('enabled')
                ->add('position', 'integer');
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
