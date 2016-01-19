<?php

namespace Techlancaster\Bundle\WebBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class EventController
 * @package Techlancaster\Bundle\WebBundle\Controller
 */
class EventController extends Controller
{
    /**
     * @Route("/")
     */
    public function indexAction()
    {
        $eventsRepo = $this->get('doctrine.orm.entity_manager')->getRepository('TechlancasterWebBundle:Event');

        $events = $eventsRepo->findAll();

        return new JsonResponse($events);
    }
}
