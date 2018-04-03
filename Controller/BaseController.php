<?php

namespace Musicjerm\Bundle\JermBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Response;

class BaseController extends Controller
{
    /**
     * @param UserInterface $user
     * @param $entity
     * @return Response
     */
    public function indexAction(UserInterface $user = null, $entity)
    {

        return $this->render('@JermBundle/Base/index.html.twig', array(
            'test_entity' => $entity
        ));
    }
}