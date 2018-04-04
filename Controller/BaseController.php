<?php

namespace Musicjerm\Bundle\JermBundle\Controller;

use Musicjerm\Bundle\JermBundle\Model\NavModel;
use Musicjerm\Bundle\JermBundle\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

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

    /**
     * @param Request $request
     * @param UserInterface|null $user
     * @return Response
     */
    public function navAction(Request $request, UserInterface $user = null)
    {
        $configDir = $this->getParameter('kernel.project_dir') . '/src/JBConfig';

        /** @var FileLocator $fileLocator */
        $fileLocator = new FileLocator([$configDir]);
        $configFile = $fileLocator->locate('nav.yaml');

        $yamlNav = Yaml::parse(file_get_contents($configFile));

        $params = $request->get('current_params');
        if ($request->get('current_route') == 'jerm_bundle_data_index'){
            unset($params['filter_preset']);
            unset($params['column_preset']);
        }

        /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationChecker $authChecker */
        $authChecker = $this->get('security.authorization_checker');
        $navModel = new NavModel($authChecker);
        $navModel
            ->setNavData($yamlNav)
            ->setCurrentRoute($request->get('current_route'))
            ->setCurrentParams($params)
            ->setUserRoles($user ? $user->getRoles() : null);

        $navModel->buildNav();

        $debugMessage = null;
        if (empty($navModel->getNavOutput() && $this->getParameter('kernel.environment') == 'dev')){
            $debugMessage = 'Please configure ' . $configDir . '/Nav.yaml';
        }

        return $this->render('@JermBundle/Base/nav.html.twig', array(
            'nav' => $navModel->getNavOutput(),
            'debug_message' => $debugMessage
        ));
    }

    /**
     * @param UserInterface|null $user
     * @return Response
     */
    public function messageAction(UserInterface $user = null)
    {
        /**
         * @var NotificationRepository $notificationRepo
         */
        $notificationRepo = $this->getDoctrine()->getRepository('JermBundle:Notification');
        $messages = $notificationRepo->getLatest($user);
        $unreadCount = intval($notificationRepo->countUnread($user)[0][1]);

        return $this->render('@JermBundle/Base/messages.html.twig', array(
            'plurality' => $unreadCount === 1 ? 'notification' : 'notifications',
            'unread_count' => $unreadCount,
            'messages' => $messages
        ));
    }
}