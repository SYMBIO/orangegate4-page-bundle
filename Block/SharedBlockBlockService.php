<?php

namespace Symbio\OrangeGate\PageBundle\Block;

use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Model\BlockManagerInterface;
use Sonata\PageBundle\Admin\SharedBlockAdmin;
use Sonata\PageBundle\Entity\BlockManager;
use Sonata\PageBundle\Model\Block;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Templating\EngineInterface;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\CoreBundle\Validator\ErrorElement;

use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\BlockBundle\Block\BaseBlockService;

use Sonata\PageBundle\Exception\PageNotFoundException;
use Sonata\PageBundle\Site\SiteSelectorInterface;
use Sonata\PageBundle\CmsManager\CmsManagerSelectorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Render a shared block
 */
class SharedBlockBlockService extends BaseBlockService
{
    /**
     * @var SharedBlockAdmin
     */
    private $sharedBlockAdmin;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var BlockManagerInterface
     */
    private $blockManager;

    /**
     * @param string                $name
     * @param EngineInterface       $templating
     * @param ContainerInterface    $container
     * @param BlockManagerInterface $blockManager
     */
    public function __construct($name, EngineInterface $templating, ContainerInterface $container, BlockManagerInterface $blockManager)
    {
        $this->name = $name;
        $this->templating = $templating;
        $this->container = $container;
        $this->blockManager = $blockManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $block = $blockContext->getBlock();

        if (!$block->getSetting('blockId') instanceof BlockInterface) {
            $this->load($block);
        }

        /** @var Block $sharedBlock */
        $sharedBlock = $block->getSetting('blockId');
        $sharedBlock->setPage($block->getPage());

        return $this->renderResponse($blockContext->getTemplate(), array(
            'block'       => $blockContext->getBlock(),
            'settings'    => $blockContext->getSettings(),
            'sharedBlock' => $sharedBlock
        ), $response);
    }

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
        $formMapper->add('translations', 'orangegate_translations', array(
            'label' => false,
            'locales' => $block->getSite()->getLocales(),
            'fields' => array(
                'enabled' => array(
                    'field_type' => 'checkbox',
                    'required' => false,
                    'label' => 'Povoleno'
                ),
                'settings' => array(
                    'field_type' => 'sonata_type_immutable_array',
                    'label' => false,
                    'keys' => array(
                        array($this->getBlockBuilder($formMapper), null, array()),
                    )
                )
            )
        ));
    }

    /**
     * @return SharedBlockAdmin
     */
    protected function getSharedBlockAdmin()
    {
        if (!$this->sharedBlockAdmin) {
            $this->sharedBlockAdmin = $this->container->get('sonata.page.admin.shared_block');
        }

        return $this->sharedBlockAdmin;
    }

    /**
     * @param \Sonata\AdminBundle\Form\FormMapper $formMapper
     *
     * @return \Symfony\Component\Form\FormBuilder
     */
    protected function getBlockBuilder(FormMapper $formMapper)
    {
        // simulate an association ...
        $fieldDescription = $this->getSharedBlockAdmin()->getModelManager()->getNewFieldDescriptionInstance($this->sharedBlockAdmin->getClass(), 'block');
        $fieldDescription->setAssociationAdmin($this->getSharedBlockAdmin());
        $fieldDescription->setAdmin($formMapper->getAdmin());
        $fieldDescription->setOption('edit', 'list');
        $fieldDescription->setAssociationMapping(array(
            'fieldName' => 'block',
            'type'      => \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE
        ));

        return $formMapper->create('blockId', 'sonata_type_model_list', array(
            'sonata_field_description' => $fieldDescription,
            'class'                    => $this->getSharedBlockAdmin()->getClass(),
            'model_manager'            => $this->getSharedBlockAdmin()->getModelManager(),
            'label'                    => 'block',
            'required'                 => false
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Sdílený blok';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultSettings(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'template' => 'SonataPageBundle:Block:block_shared_block.html.twig',
            'blockId'  => '',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function load(BlockInterface $block)
    {
        foreach ($block->getTranslations() as $trans) {
            $sharedBlock = $trans->getSetting('blockId', null);

            if (is_int($sharedBlock)) {
                $sharedBlock = $this->blockManager->findOneBy(array('id' => $sharedBlock));
            }

            $trans->setSetting('blockId', $sharedBlock);
        }

        $sharedBlock = $block->getSetting('blockId', null);

        if (is_int($sharedBlock)) {
            $sharedBlock = $this->blockManager->findOneBy(array('id' => $sharedBlock));
        }

        $block->setSetting('blockId', $sharedBlock);
    }

    /**
     * {@inheritdoc}
     */
    public function prePersist(BlockInterface $block)
    {
        foreach ($block->getTranslations() as $trans) {
            $trans->setSetting('blockId', is_object($trans->getSetting('blockId')) ? $trans->getSetting('blockId')->getId() : null);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(BlockInterface $block)
    {
        $this->prePersist($block);
    }
}
