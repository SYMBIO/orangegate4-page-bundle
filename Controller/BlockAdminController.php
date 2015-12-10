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
        if ($object->getSite()) {
            $request->query->set('site', $object->getSite()->getId());
            $this->get('orangegate.site.pool')->getCurrentSite($request);
        }

        parent::preEdit($request, $object);
    }
}
