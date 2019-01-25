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

use Doctrine\ORM\EntityManager;
use Symbio\OrangeGate\AdminBundle\Admin\Admin as BaseAdmin;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

use Sonata\PageBundle\Exception\PageNotFoundException;
use Sonata\PageBundle\Exception\InternalErrorException;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Model\PageManagerInterface;
use Sonata\PageBundle\Model\SiteManagerInterface;

use Sonata\Cache\CacheManagerInterface;

use Symbio\OrangeGate\PageBundle\Entity\Redirect;
use Symfony\Component\Validator\Constraints as Assert;

use Knp\Menu\ItemInterface as MenuItemInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Admin definition for the Redirect class
 */
class RedirectAdmin extends BaseAdmin
{
    /**
     * @var SiteManagerInterface
     */
    protected $siteManager;

    public function __construct($code, $class, $baseControllerName, EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        $this->formOptions['constraints'] = array(
            new Assert\Callback(array($this, 'validateSourceUrl')),
        );

        parent::__construct($code, $class, $baseControllerName);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('enabled')
            ->add('sourceUrl')
            ->add('destinationUrl')
            ->add('type')
            ->add('note')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('enabled', null, array('editable' => true))
            ->addIdentifier('sourceUrl')
            ->addIdentifier('destinationUrl')
            ->addIdentifier('type')
            ->addIdentifier('note')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('enabled')
            ->add('sourceUrl', null, ['label' => 'form.label_source_url'])
            ->add('destinationUrl', null, ['label' => 'form.label_destination_url'])
            ->add('type', null, [], 'choice' , ['choices' => [301=>301,302=>302]])
            ->add('note', null, ['label' => 'form.label_note'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        // get site
        $site = null;
        if ($this->getSubject()) {
            $site = $this->getSubject()->getSite();
        }

        if ($this->isGranted('ADMIN')) {
            $formMapper
                ->add('site', null, array('required' => true, 'read_only' => true))
                ->end();
        }

        $formMapper
            ->add('enabled')
            ->add('type', 'choice' , ['choices' => [301=>301,302=>302]])
            ->add('sourceUrl')
            ->add('destinationUrl')
            ->add('note')
            ->add('position', null, ['required' => false])
            ->end();
    }

    /**
     * Check if source URL exists
     *
     * @param Redirect $redirect
     * @param ExecutionContextInterface $context
     */
    public function validateSourceUrl(Redirect $redirect, ExecutionContextInterface $context)
    {
        if (!$this->id($this->getSubject()) && $redirect->getSourceUrl()) {
            $foundRedirect = $this->entityManager->getRepository('SymbioOrangeGatePageBundle:Redirect')->findOneBy([
                'sourceUrl' => $redirect->getSourceUrl(),
                'enabled' => true
            ]);

            if (!$foundRedirect) {
                if (strpos($redirect->getSourceUrl(), '://') !== false) {
                    $parsedSourceUrl = parse_url($redirect->getSourceUrl());
                    $sourcePath = $parsedSourceUrl['path'] . (isset($parsedSourceUrl['query']) && $parsedSourceUrl['query'] ? '?' . $parsedSourceUrl['query'] : '');
                    $foundRedirect = $this->entityManager->getRepository('SymbioOrangeGatePageBundle:Redirect')->findOneBy([
                        'sourceUrl' => $sourcePath,
                        'enabled' => true
                    ]);
                } else {
                    $sourcePath = $redirect->getSourceUrl();
                }

                if (!$foundRedirect) {
                    $foundRedirect = $this->entityManager->createQuery('
                        SELECT r
                        FROM SymbioOrangeGatePageBundle:Redirect r 
                        WHERE
                          r.sourceUrl LIKE :source_path
                          AND r.enabled = :enabled
                    ')
                    ->setParameter('source_path', '%' . $sourcePath)
                    ->setParameter('enabled', true)
                    ->execute();
                }
            }

            if ($foundRedirect) {
                $context->buildViolation('page_redirect.source_url_exists')
                    ->atPath('sourceUrl')
                    ->addViolation();
            }
        }
    }

}
