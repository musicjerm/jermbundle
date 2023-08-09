<?php

namespace Musicjerm\Bundle\JermBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Musicjerm\Bundle\JermBundle\Entity\Notification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

class NotificationController extends AbstractController
{
    public function __construct(private readonly ManagerRegistry $doctrine) {}

    /**
     * @param integer $id
     * @return Response
     */
    public function viewAction($id)
    {
        /** @var Notification $notification */
        $notificationRepo = $this->doctrine->getRepository('Musicjerm\Bundle\JermBundle\Entity\Notification');
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
        $em = $this->doctrine->getManager();
        $em->flush();

        if ($notification->getHyperlink() !== null){
            return $this->redirect($notification->getHyperlink());
        }

        return $this->render('@JermBundle/Modal/notification_view.html.twig', array(
            'notification' => $notification,
            'notification_status' => $status
        ));
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @return Response
     */
    public function markReadAction(Request $request, UserInterface $user)
    {
        $em = $this->doctrine->getManager();
        $notificationRepo = $em->getRepository('Musicjerm\Bundle\JermBundle\Entity\Notification');

        foreach ($request->get('id') as $id){
            $notification = $notificationRepo->find($id);

            if (!$notification || $notification->getUser() !== $user){
                throw new AccessDeniedException();
            }

            $notification->setUnread(0);
        }

        $em->flush();

        return $this->render('@JermBundle/Modal/notification.html.twig', array(
            'message' => 'Success!',
            'modal_size' => 'modal-sm',
            'type' => 'success',
            'full_refresh' => true,
            'fade' => true
        ));
    }
}