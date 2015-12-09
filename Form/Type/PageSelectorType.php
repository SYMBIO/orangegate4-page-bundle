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

    protected $isEditor = false;

    /**
     * @param PageManagerInterface $manager
     */
    public function __construct(PageManagerInterface $manager, SecurityContextInterface $securityContext)
    {
        $this->manager = $manager;

        $this->securityContext = $securityContext;

        $this->isEditor = $this->securityContext->isGranted(array('ROLE_SONATA_PAGE_ADMIN_PAGE_EDITOR','ROLE_SONATA_PAGE_ADMIN_PAGE_ADMIN'));
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

        $editedPage = $options['page'];

        $pages = $this->manager->loadPages($options['site']);

        $choices = array();
        $parents = array();

        foreach ($pages as $page) {
            if ($page->isInternal()) { // internal pages cannot be selected
                continue;
            }

            // user has PageAdmin editor or admin role - render whole tree
            if ($this->isEditor && null === $page->getParent()) {
                $this->childWalker($page, null, $choices, 1);
            }
            // user has defined ACL rights - render from allowed pages parents
            elseif (!$this->isEditor && $this->securityContext->isGranted('EDIT', $page)) {
                // find the top not-granted parent
                $topNotGrantedParent = $this->getTopNotGrantedParent($page);
                // check if parent is not walked through
                if ($topNotGrantedParent && !in_array($topNotGrantedParent->getId(), $parents)) {
                    $parents[] = $topNotGrantedParent->getId();
                    $this->childWalker($topNotGrantedParent, null, $choices, 1, ($editedPage->getParent() && $topNotGrantedParent->getId() == $editedPage->getParent()->getId()));
                }
            }
        }

        return $choices;
    }

    /**
     * Return top parent which is not granted to edit
     *
     * @param PageInterface $page
     * @return PageInterface $parent
     */
    private function getTopNotGrantedParent(PageInterface $page)
    {
        while ($page->getParent() && $this->securityContext->isGranted('EDIT', $page->getParent())) {
            $page = $page->getParent();
        }
        return $page->getParent() ?: $page;
    }

    /**
     * @param PageInterface $page
     * @param PageInterface $currentPage
     * @param array         $choices
     * @param int           $level
     */
    private function childWalker(PageInterface $page, PageInterface $currentPage = null, &$choices, $level = 1, $addNotGrantedPage = false)
    {
        if (!($currentPage && $currentPage->getId() == $page->getId())) {
            if ($level > 1 || $this->isEditor || $this->securityContext->isGranted('EDIT', $page) || $addNotGrantedPage) {
                $choices[$page->getId()] = $page->getLongName();
            }
            foreach ($page->getChildren() as $child) {
                if ($this->isEditor || $this->securityContext->isGranted('EDIT', $child)) {
                    $this->childWalker($child, $currentPage, $choices, $level + 1);
                }
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
