<?php

namespace Symbio\OrangeGate\PageBundle\Form\Type;

use Symbio\OrangeGate\PageBundle\Entity\SitePool;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList;

/**
 * Select a site
 */
class SiteSelectorType extends AbstractType
{
    /**
     * @var SitePool
     */
    protected $sitePool;

    /**
     * @param SitePool $sitePool
     */
    public function __construct(SitePool $sitePool)
    {
        $this->sitePool = $sitePool;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'choice_list' => new SimpleChoiceList($this->getChoices()),
            'class'       => 'SymbioOrangeGatePageBundle:Site',
        ));
    }

    /**
     * @return array
     */
    public function getChoices()
    {
        $sites = $this->sitePool->getSites();
        $choices = array();

        foreach ($sites as $site) {
            $choices[$site->getId()] = $site->getName();
        }

        return $choices;
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
        return 'orangegate_site_selector';
    }
}
