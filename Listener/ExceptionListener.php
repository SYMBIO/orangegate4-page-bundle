<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symbio\OrangeGate\PageBundle\Listener;

use FOS\RestBundle\Controller\Annotations\Get;
use Psr\Log\LoggerInterface;
use Sonata\AdminBundle\Route\AdminPoolLoader;
use Sonata\PageBundle\CmsManager\CmsManagerSelectorInterface;
use Sonata\PageBundle\CmsManager\DecoratorStrategyInterface;
use Sonata\PageBundle\Exception\InternalErrorException;
use Sonata\PageBundle\Page\PageServiceManagerInterface;
use Sonata\PageBundle\Site\SiteSelectorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Templating\EngineInterface;
use Sonata\PageBundle\Listener\ExceptionListener as BaseListener;
use Sonata\AdminBundle\Admin\Pool;

/**
 * ExceptionListener.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class ExceptionListener extends BaseListener
{
    /**
     * @var SiteSelectorInterface
     */
    protected $siteSelector;

    /**
     * @var CmsManagerSelectorInterface
     */
    protected $cmsManagerSelector;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var PageServiceManagerInterface
     */
    protected $pageServiceManager;

    /**
     * @var DecoratorStrategyInterface
     */
    protected $decoratorStrategy;

    /**
     * @var array
     */
    protected $httpErrorCodes;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * @var bool
     */
    protected $status;

    protected $tokenStorage;

    protected $adminPool;

    /**
     * Constructor.
     *
     * @param SiteSelectorInterface       $siteSelector       Site selector
     * @param CmsManagerSelectorInterface $cmsManagerSelector CMS Manager selector
     * @param bool                        $debug              Debug mode
     * @param EngineInterface             $templating         Templating engine
     * @param PageServiceManagerInterface $pageServiceManager Page service manager
     * @param DecoratorStrategyInterface  $decoratorStrategy  Decorator strategy
     * @param array                       $httpErrorCodes     An array of http error codes' routes
     * @param LoggerInterface|null        $logger             Logger instance
     */
    public function __construct(SiteSelectorInterface $siteSelector,
                                CmsManagerSelectorInterface $cmsManagerSelector,
                                $debug,
                                EngineInterface $templating,
                                PageServiceManagerInterface $pageServiceManager,
                                DecoratorStrategyInterface $decoratorStrategy,
                                array $httpErrorCodes,
                                LoggerInterface $logger = null, TokenStorage $tokenStorage, Pool $adminPool)
    {
        parent::__construct($siteSelector, $cmsManagerSelector, $debug, $templating, $pageServiceManager, $decoratorStrategy, $httpErrorCodes, $logger);
        $this->tokenStorage = $tokenStorage;
        $this->adminPool = $adminPool;
    }

    /**
     * Handles a kernel exception.
     *
     * @param GetResponseForExceptionEvent $event
     *
     * @throws \Exception
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($this->tokenStorage->getToken()->getProviderKey() === 'admin' && ($event->getException() instanceof AccessDeniedHttpException || $event->getException() instanceof AccessDeniedException)) {
            $this->handleAccessDeniedError($event);
        }

        parent::onKernelException($event);
    }

    private function handleAccessDeniedError(GetResponseForExceptionEvent $event)
    {
        $content = $this->templating->render('@SymbioOrangeGatePage/access_denied_error.html.twig', array(
            'exception' => $event->getException(),
            'admin_pool' => $this->adminPool,
            'locale' => $event->getRequest()->getLocale()
        ));

        $event->setResponse(new Response($content, 403));
    }


}
