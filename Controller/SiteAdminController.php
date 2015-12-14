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

use Sonata\PageBundle\Controller\SiteAdminController as Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Page Admin Controller
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class SiteAdminController extends Controller
{
    public function snapshotsAction()
    {
        if (false === $this->get('sonata.page.admin.snapshot')->isGranted('CREATE')) {
            throw new AccessDeniedException();
        }

        $id = $this->get('request')->get($this->admin->getIdParameter());

        $object = $this->admin->getObject($id);

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
        }

        $this->admin->setSubject($object);

        $translatableListener = $this->get('gedmo.listener.translatable');
        $translatableListener->setTranslatableLocale($object->getLocale());

        $request = $this->get('request');

        if ($request->getMethod() === "POST") {
            $this->get('sonata.notification.backend')
                ->createAndPublish('sonata.page.create_snapshots', array(
                    'siteId' => $object->getId(),
                    'mode'   => 'async'
                ));

            $this->addFlash('sonata_flash_success', $this->admin->trans('flash_snapshots_created_success'));

            return new RedirectResponse($this->admin->generateUrl('edit', array('id' => $object->getId())));
        }

        if ($request->getMethod() === "GET" && $request->query->get('direct', false)) {
            $this->get('sonata.notification.backend')
                ->createAndPublish('sonata.page.create_snapshots', array(
                    'siteId' => $object->getId(),
                    'mode'   => 'async'
                ));

            $this->addFlash('sonata_flash_success', $this->admin->trans('flash_snapshots_created_success'));

            return new RedirectResponse($request->headers->get('referer'));
        }

        return $this->render('SonataPageBundle:SiteAdmin:create_snapshots.html.twig', array(
            'action'  => 'snapshots',
            'object'  => $object
        ));
    }

}
