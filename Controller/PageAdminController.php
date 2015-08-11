<?php

namespace Symbio\OrangeGate\PageBundle\Controller;

use Sonata\PageBundle\Controller\PageAdminController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Page Admin Controller
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class PageAdminController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request = NULL)
    {
        return new RedirectResponse($this->admin->generateUrl('tree'));
    }

    public function createAction(Request $request = NULL)
    {
        if ($parentId = $request->query->get('parentId')) {
            $parent = $this->admin->getObject($parentId);
            if ($parent) {
                $request->request->set('siteId', $parent->getSite()->getId());
                $request->request->set('parentId', $parent->getId());
            }
        }

        if (false === $this->admin->isGranted('CREATE') && (!isset($parent) || !$this->admin->isGranted('EDIT', $parent))) {
            throw new AccessDeniedException();
        }

        if ($request->getMethod() === 'GET' && !$request->get('siteId')) {
            $sites = $this->get('sonata.page.manager.site')->findBy(array());

            if (count($sites) == 1) {
                return $this->redirect($this->admin->generateUrl('create', array(
                    'siteId' => $sites[0]->getId(),
                    'uniqid' => $this->admin->getUniqid()
                )));
            }

            try {
                $current = $this->get('sonata.page.site.selector')->retrieve();
            } catch (\RuntimeException $e) {
                $current = false;
            }

            return $this->render('SonataPageBundle:PageAdmin:select_site.html.twig', array(
                'sites' => $sites,
                'current' => $current,
            ));
        }

        // the key used to lookup the template
        $templateKey = 'edit';

        $object = $this->admin->getNewInstance();

        if (isset($parent)) {
            $object->setParent($parent);
        }

        $this->admin->setSubject($object);

        /** @var $form \Symfony\Component\Form\Form */
        $form = $this->admin->getForm();
        $form->setData($object);

        if ($this->getRestMethod() == 'POST') {
            $form->submit($this->get('request'));

            $isFormValid = $form->isValid();

            // persist if the form was valid and if in preview mode the preview was approved
            if ($isFormValid && (!$this->isInPreviewMode() || $this->isPreviewApproved())) {

                if (false === $this->admin->isGranted('CREATE', $object)) {
                    throw new AccessDeniedException();
                }

                try {
                    $object = $this->admin->create($object);

                    if ($this->isXmlHttpRequest()) {
                        return $this->renderJson(array(
                            'result' => 'ok',
                            'objectId' => $this->admin->getNormalizedIdentifier($object)
                        ));
                    }

                    $this->addFlash(
                        'sonata_flash_success',
                        $this->admin->trans(
                            'flash_create_success',
                            array('%name%' => $this->admin->toString($object)),
                            'SonataAdminBundle'
                        )
                    );

                    // redirect to edit mode
                    return $this->redirectTo($object);

                } catch (ModelManagerException $e) {
                    $this->logModelManagerException($e);

                    $isFormValid = false;
                }
            }

            // show an error message if the form failed validation
            if (!$isFormValid) {
                if (!$this->isXmlHttpRequest()) {
                    $this->addFlash(
                        'sonata_flash_error',
                        $this->admin->trans(
                            'flash_create_error',
                            array('%name%' => $this->admin->toString($object)),
                            'SonataAdminBundle'
                        )
                    );
                }
            } elseif ($this->isPreviewRequested()) {
                // pick the preview template if the form was valid and preview was requested
                $templateKey = 'preview';
                $this->admin->getShow();
            }
        }

        $view = $form->createView();

        // set the theme for the current Admin Form
        $this->get('twig')->getExtension('form')->renderer->setTheme($view, $this->admin->getFormTheme());

        return $this->render($this->admin->getTemplate($templateKey), array(
            'action' => 'create',
            'form' => $view,
            'object' => $object,
        ));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function treeAction(Request $request = null)
    {
        if (false === $this->admin->isGranted('LIST')) {
            throw new AccessDeniedException();
        }

        $sitePool = $this->get('orangegate.site.pool');
        $sites = $sitePool->getSites();
        $currentSite = $sitePool->getCurrentSite($request);

        if ($currentSite) {
            $pageManager = $this->get('sonata.page.manager.page');
            $pages = $pageManager->loadPages($currentSite);
        } else {
            $pages = array();
        }

        $datagrid = $this->admin->getDatagrid();
        $formView = $datagrid->getForm()->createView();

        $this->get('twig')->getExtension('form')->renderer->setTheme($formView, $this->admin->getFilterTheme());

        return $this->render('SonataPageBundle:PageAdmin:tree.html.twig', array(
            'action' => 'tree',
            'sites' => $sites,
            'currentSite' => $currentSite,
            'pages' => $pages,
            'form' => $formView,
            'csrf_token' => $this->getCsrfToken('sonata.batch'),
        ));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @throws NotFoundHttpException
     */
    public function composeAction(Request $request = NULL)
    {
        $id = $this->get('request')->get($this->admin->getIdParameter());
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
        }

        if (false === $this->admin->isGranted('EDIT', $object)) {
            throw new AccessDeniedException();
        }

        return parent::composeAction($request);
    }

    public function showAction($id = null, Request $request = NULL)
    {
        $page = $this->admin->getObject($id);
        if (!$page->isHybrid() && !$page->isInternal()) {
            return new RedirectResponse($this->get('router')->generate('page_slug', array('path' => $page->getUrl())));
        }

        return parent::showAction($request);
    }

    /**
     * @inheritdoc
     */
    public function composeContainerShowAction(Request $request = null)
    {
        if (false === $this->get('sonata.page.admin.block')->isGranted('LIST')) {
            throw new AccessDeniedException();
        }

        $id    = $request->get($this->admin->getIdParameter());
        $block = $this->get('sonata.page.admin.block')->getObject($id);
        if (!$block) {
            throw new NotFoundHttpException(sprintf('unable to find the block with id : %s', $id));
        }

        $blockServices = $this->get('sonata.block.manager')->getServicesByContext('sonata_page_bundle', false);

        // filter service using the template configuration
        if ($page = $block->getPage()) {
            $template = $this->get('sonata.page.template_manager')->get($page->getTemplateCode());

            $container = $template->getContainer($block->getSetting('code'));

            if (isset($container['blocks']) && count($container['blocks']) > 0) {
                foreach ($blockServices as $code => $service) {
                    if (!in_array($code, $container['blocks'])) {
                        unset($blockServices[$code]);
                    }
                }
            }

            foreach ($blockServices as $code => $service) {
                if (is_callable(array($service, 'canDisplayIn')) && !$service->canDisplayIn($block, $template)) {
                    unset($blockServices[$code]);
                }
            }
        }

        return $this->render('SonataPageBundle:PageAdmin:compose_container_show.html.twig', array(
            'blockServices' => $blockServices,
            'container'     => $block,
            'page'          => $block->getPage(),
        ));
    }

    /**
     * Delete action
     *
     * @param int|string|null $id
     * @param Request         $request
     *
     * @return Response|RedirectResponse
     *
     * @throws NotFoundHttpException If the object does not exist
     * @throws AccessDeniedException If access is not granted
     */
    public function deleteAction($id, Request $request = null)
    {
        if (null === $request) {
            $request = $this->getRequest();
        }
        $id      = $request->get($this->admin->getIdParameter());
        $object  = $this->admin->getObject($id);

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
        }

        if (false === $this->admin->isGranted('DELETE', $object)) {
            throw new AccessDeniedException();
        }

        $preResponse = $this->preDelete($request, $object);
        if ($preResponse !== null) {
            return $preResponse;
        }

        if ($this->getRestMethod($request) === 'DELETE') {
            // check the csrf token
            $this->validateCsrfToken('sonata.delete', $request);

            $objectName = $this->admin->toString($object);

            try {
                $this->admin->delete($object);

                if ($this->isXmlHttpRequest($request)) {
                    return $this->renderJson(array('result' => 'ok'), 200, array(), $request);
                }

                $this->addFlash(
                    'sonata_flash_success',
                    $this->admin->trans(
                        'flash_delete_success',
                        array('%name%' => $this->escapeHtml($objectName)),
                        'SonataAdminBundle'
                    )
                );
            } catch (ModelManagerException $e) {
                $this->handleModelManagerException($e);

                if ($this->isXmlHttpRequest($request)) {
                    return $this->renderJson(array('result' => 'error'), 200, array(), $request);
                }

                $this->addFlash(
                    'sonata_flash_error',
                    $this->admin->trans(
                        'flash_delete_error',
                        array('%name%' => $this->escapeHtml($objectName)),
                        'SonataAdminBundle'
                    )
                );
            }

            return $this->redirectTo($object, $request);
        }


//        return $this->render($this->admin->getTemplate('delete'), array(
        return $this->render('SymbioOrangeGatePageBundle:PageAdmin:delete.html.twig', array(
            'block_refs' => $this->get('sonata.page.manager.page')->findBlockLinksTo($object),
            'snap_refs'  => $this->get('sonata.page.manager.page')->findSnapshotLinksTo($object),
            'object'     => $object,
            'action'     => 'delete',
            'csrf_token' => $this->getCsrfToken('sonata.delete'),
        ), null, $request);
    }
}
