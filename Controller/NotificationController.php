<?php

namespace AppBundle\Controller\Module;

use Musicjerm\Bundle\JermBundle\Entity\Notification;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    /**
     * @param integer $id
     * @return Response
     */
    public function viewAction($id)
    {
        /** @var Notification $notification */
        $notificationRepo = $this->getDoctrine()->getRepository('JermBundle:Notification');
        $notification = $notificationRepo->find($id);

        if (null === $notification){
            return $this->render('@JermBundle/Modal/notification.html.twig', array(
                'message' => 'Notification does not exist',
                'type' => 'error',
                'refresh' => true
            ));
        }

        $status = $notification->getStatus();

        $notification->setUnread(0);
        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return $this->render('@JermBundle/Modal/notification_view.html.twig', array(
            'notification' => $notification,
            'notification_status' => $status
        ));
    }
}