<?php

namespace Symbio\OrangeGate\PageBundle\Form\Type;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Sonata\PageBundle\Model\PageManagerInterface;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Model\SiteInterface;

/**
 * Select a page
 */
class PageSelectorType extends AbstractType
{
    protected $manager;

    protected $securityContext;

    /**
     * @param PageManagerInterface $manager
     */
    public function __construct(PageManagerInterface $manager, SecurityContextInterface $securityContext)
    {
        $this->manager = $manager;

        $this->securityContext = $securityContext;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $that = $this;

        $resolver->setDefaults(array(
            'page'              => null,
            'site'              => null,
            'choice_list'       => function (Options $opts, $previousValue) use ($that) {
                return new SimpleChoiceList($that->getChoices($opts));
            },
            'filter_choice'     => array(
                'root'              => false,
            ),
        ));
    }

    /**
     * @param Options $options
     *
     * @return array
     */
    public function getChoices(Options $options)
    {
        if (!$options['site'] instanceof SiteInterface) {
            return array();
        }

        $filter_choice = array_merge(array(
            'root' => false,
        ), $options['filter_choice']);

        $pages = $this->manager->loadPages($options['site']);

        $choices = array();

        foreach ($pages as $page) {
            if (
                !$page->isInternal() // internal cannot be selected
                && null === $page->getParent()
                && $this->securityContext->isGranted('EDIT', $page)
            ) {
                $this->childWalker($page, null, $choices, 1);
            }
        }

        return $choices;
    }

    /**
     * @param PageInterface $page
     * @param PageInterface $currentPage
     * @param array         $choices
     * @param int           $level
     */
    private function childWalker(PageInterface $page, PageInterface $currentPage = null, &$choices, $level = 1)
    {
        if (
            !($currentPage && $currentPage->getId() == $page->getId())
        ) {
            $choices[$page->getId()] = $page->getLongName();

            foreach ($page->getChildren() as $child) {
                    $this->childWalker($child, $currentPage, $choices, $level + 1);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'sonata_type_model';
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'orangegate_page_selector';
    }
}
