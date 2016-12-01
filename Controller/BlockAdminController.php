<?php

namespace Symbio\OrangeGate\PageBundle\Controller;

use Sonata\PageBundle\Controller\BlockAdminController as Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Block Admin Controller
 */
class BlockAdminController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request = null)
    {
        return parent::listAction($request);
    }

    public function preEdit(Request $request, $object)
    {
        // set current site to currently edited block's site
        if ($site = $object->getSite()) {
            $request->query->set('site', $site->getId());
        } elseif ($object->getPage() && ($site = $object->getPage()->getSite())) {
            $request->query->set('site', $site->getId());
        }

        $this->get('orangegate.site.pool')->getCurrentSite($request);
        $translatableListener = $this->get('gedmo.listener.translatable');
        $translatableListener->setTranslatableLocale($site->getLocale());

        parent::preEdit($request, $object);
    }
}
