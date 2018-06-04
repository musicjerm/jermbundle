<?php

namespace Musicjerm\Bundle\JermBundle\Controller;

use Musicjerm\Bundle\JermBundle\Model\AppUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class AppConfigController extends Controller
{
    /**
     * @return Response
     */
    public function indexAction(): Response
    {
        // build new updater
        $appUpdater = new AppUpdater(
            $this->getParameter('kernel.project_dir'),
            $this->getParameter('git_user'),
            $this->getParameter('git_pass'),
            $this->getParameter('git_repo')
        );

        // fetch remote, check for updates
        !$appUpdater->fetchRemote() ?: $appUpdater->checkUpdates();

        // set message if not already set
        if ($appUpdater->message === null){
            $appUpdater->message = $appUpdater->commitsAvailable . ' update(s) available.';
        }

        // build array for tools
        $tools = array();

        // git updater
        $tools[] = array(
            'path' => $this->generateUrl('jerm_bundle_app_git_update'),
            'label' => 'Git Pull',
            'class' => 'btn-success',
            'icon' => 'fa-cloud-download'
        );

        // if dev, use composer update.  if prod, composer install
        if ($_SERVER['APP_ENV'] === 'dev'){
            $tools[] = array(
                'path' => $this->generateUrl('jerm_bundle_app_composer_update', ['method' => 'update']),
                'label' => 'Composer Update',
                'class' => 'btn-warning',
                'icon' => 'fa-chevron-circle-up'
            );
        }

        if ($_SERVER['APP_ENV'] === 'prod'){
            $tools[] = array(
                'path' => $this->generateUrl('jerm_bundle_app_composer_update', ['method' => 'install']),
                'label' => 'Composer Install',
                'class' => 'btn-primary',
                'icon' => 'fa-chevron-circle-up'
            );
        }

        // doctrine updater
        $tools[] = array(
            'path' => $this->generateUrl('jerm_bundle_app_doctrine_update', ['method' => 'check']),
            'label' => 'Doctrine Update',
            'class' => 'btn-info',
            'icon' => 'fa-database'
        );

        // clear, warm cache
        $tools[] = array(
            'path' => $this->generateUrl('jerm_bundle_app_cache'),
            'label' => 'Clear/Warm Cache',
            'class' => 'btn-default',
            'icon' => 'fa-eraser'
        );

        // return view to user
        return $this->render('@JermBundle/Base/app_config.html.twig', array(
            'update_status' => $appUpdater->message,
            'commits_available' => $appUpdater->commitsAvailable,
            'tools' => $tools
        ));
    }

    /**
     * @return Response
     */
    public function gitUpdate(): Response
    {
        // app updater
        $appUpdater = new AppUpdater(
            $this->getParameter('kernel.project_dir'),
            $this->getParameter('git_user'),
            $this->getParameter('git_pass'),
            $this->getParameter('git_repo')
        );

        // pull git updates
        $type = $appUpdater->pullUpdates() ? 'success' : 'error';

        // return response to user
        return $this->render('@JermBundle/Modal/notification.html.twig', array(
            'message' => ucfirst($type) . '!' . $appUpdater->message,
            'type' => $type
        ));
    }

    /**
     * @param string $method
     * @return Response
     * @throws \Exception
     */
    public function composerUpdate(string $method): Response
    {
        // make sure method is correct else return error
        if (($_SERVER['APP_ENV'] === 'dev' && $method !== 'update') || ($_SERVER['APP_ENV'] === 'prod' && $method !== 'install')){
            return $this->render('@JermBundle/Modal/notification.html.twig', array(
                'message' => 'This process has not been configured correctly, please contact and admin.',
                'type' => 'error'
            ));
        }

        // app updater
        $appUpdater = new AppUpdater($this->getParameter('kernel.project_dir'), null, null, null);

        // run composer update/install
        $type = $appUpdater->composerUpdate($method) ? 'success' : 'error';

        // return response to user
        return $this->render('@JermBundle/Modal/notification.html.twig', array(
            'message' => ucfirst($type) . '!' . $appUpdater->message,
            'type' => $type
        ));
    }

    /**
     * @param string $method
     * @return Response
     */
    public function doctrineUpdate(string $method): Response
    {
        // make sure method is correct
        if (!\in_array($method, ['check', 'force'])){
            return $this->render('@JermBundle/Modal/notification.html.twig', array(
                'message' => 'This process has not been configured correctly, please contact and admin.',
                'type' => 'error'
            ));
        }

        // app updater
        $appUpdater = new AppUpdater($this->getParameter('kernel.project_dir'), null, null, null);

        // if method is check, check for schema differences
        if ($method === 'check'){
            $type = $appUpdater->doctrineUpdate('--dump-sql') ? 'success' : 'error';

            return $this->render('@JermBundle/Modal/doctrine_check_notification.html.twig', array(
                'message' => $appUpdater->message,
                'type' => $type,
                'migrate' => substr(trim($appUpdater->message), 5, 17) === 'Nothing to update'
            ));
        }

        // if method is force, update database
        if ($method === 'force'){
            $type = $appUpdater->doctrineUpdate('--force') ? 'success' : 'error';

            // return response to user
            return $this->render('@JermBundle/Modal/notification.html.twig', array(
                'message' => ucfirst($type) . '!' . $appUpdater->message,
                'type' => $type
            ));
        }

        return new Response('Script happiness');
    }

    /**
     * @return Response
     */
    public function cacheAction(): Response
    {
        // app updater
        $appUpdater = new AppUpdater($this->getParameter('kernel.project_dir'), null, null, null);

        // run cache process
        $appUpdater->clearCache() ? $type = 'success' : $type = 'error';

        // return response to user
        return $this->render('@JermBundle/Modal/notification.html.twig', array(
            'message' => ucfirst($type) . '!' . $appUpdater->message,
            'type' => $type
        ));
    }
}