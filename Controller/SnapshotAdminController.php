<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symbio\OrangeGate\PageBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sonata\PageBundle\Controller\SnapshotAdminController as BaseController;

/**
 * Snapshot Admin Controller.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class SnapshotAdminController extends BaseController
{
    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function createAction(Request $request = null)
    {
        if (false === $this->admin->isGranted('CREATE')) {
            throw new AccessDeniedException();
        }

        $class = $this->get('sonata.page.manager.snapshot')->getClass();

        $pageManager = $this->get('sonata.page.manager.page');

        $snapshot = new $class();


        if ($request->getMethod() == 'GET' && $request->get('pageId')) {
            $page = $pageManager->findOneBy(array('id' => $request->get('pageId')));
        } elseif ($this->admin->isChild()) {
            $page = $this->admin->getParent()->getSubject();
        } else {
            $page = null; // no page selected ...
        }

        $snapshot->setPage($page);

        if ($request->getMethod() == 'GET' && $request->query->get('pageId', null) && $request->query->get('create', false)) {
            $snapshotManager = $this->get('sonata.page.manager.snapshot');
            $transformer = $this->get('sonata.page.transformer');

            $page->setEdited(false);
            $snapshot = $transformer->create($page);
            $this->admin->create($snapshot);
            $pageManager->save($page);
            $snapshotManager->enableSnapshots(array($snapshot));

            $this->addFlash('sonata_flash_success', $this->admin->trans('flash_snapshots_created_success'));

            return new RedirectResponse($request->headers->get('referer'));
        }


        $form = $this->createForm('sonata_page_create_snapshot', $snapshot);

        if ($request->getMethod() == 'POST') {
            $form->submit($request);

            if ($form->isValid()) {
                $snapshotManager = $this->get('sonata.page.manager.snapshot');
                $transformer = $this->get('sonata.page.transformer');

                $page = $form->getData()->getPage();
                $page->setEdited(false);

                $snapshot = $transformer->create($page);

                $this->admin->create($snapshot);

                $pageManager->save($page);

                $snapshotManager->enableSnapshots(array($snapshot));
            }

            return $this->redirect($this->admin->generateUrl('edit', array(
                'id' => $snapshot->getId(),
            )));
        }

        return $this->render('SonataPageBundle:SnapshotAdmin:create.html.twig', array(
            'action'  => 'create',
            'form'    => $form->createView(),
        ));
    }
}
