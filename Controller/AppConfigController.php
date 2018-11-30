<?php

namespace Musicjerm\Bundle\JermBundle\Controller;

use Musicjerm\Bundle\JermBundle\Form\AppConfigType;
use Musicjerm\Bundle\JermBundle\Form\DTO\AppConfigData;
use Musicjerm\Bundle\JermBundle\Model\AppUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AppConfigController extends AbstractController
{
    /**
     * @return Response
     */
    public function indexAction(): Response
    {
        // allow some time
        set_time_limit(1800);

        // build new updater
        $appUpdater = new AppUpdater(
            $this->getParameter('kernel.project_dir'),
            $this->getParameter('git_user'),
            $this->getParameter('git_pass'),
            $this->getParameter('git_repo')
        );

        // fetch remote, check for updates
        !$appUpdater->fetchRemote() ?: $appUpdater->checkUpdates();

        // git config
        $config = $appUpdater->getConfig();

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
            'tools' => $tools,
            'config' => $config
        ));
    }

    /**
     * @return Response
     */
    public function gitUpdate(): Response
    {
        // allow some time
        set_time_limit(1800);

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
        // allow some time
        set_time_limit(1800);

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
        // allow some time
        set_time_limit(1800);

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
        // allow some time
        set_time_limit(1800);

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

    /**
     * @param Request $request
     * @return Response
     */
    public function configureOptions(Request $request): Response
    {
        // create new app updater instance and get existing options
        $appUpdater = new AppUpdater(
            $this->getParameter('kernel.project_dir'),
            $this->getParameter('git_user'),
            $this->getParameter('git_pass'),
            $this->getParameter('git_repo')
        );
        $existingOptions = $appUpdater->getConfig();

        // set data for form
        $appConfigData = new AppConfigData();
        $appConfigData->configuredUrl = $appUpdater->getConfiguredUrl();

        if (isset($existingOptions['core.filemode'])){
            $appConfigData->fileMode = $existingOptions['core.filemode'] === 'true';
            $appConfigData->existingFileMode = $existingOptions['core.filemode'] === 'true';
        }

        if (isset($existingOptions['http.sslverify'])){
            $appConfigData->sslVerify = $existingOptions['http.sslverify'] === 'true';
            $appConfigData->existingSslVerify = $existingOptions['http.sslverify'] === 'true';
        }

        if (isset($existingOptions['remote.origin.url'])){
            $appConfigData->remoteOriginUrl = $existingOptions['remote.origin.url'];
            $appConfigData->existingRemoteOriginUrl = $existingOptions['remote.origin.url'];
        }

        // build form
        $form = $this->createForm(AppConfigType::class, $appConfigData, array(
            'action' => $this->generateUrl('jerm_bundle_app_config_options')
        ));

        // process form
        $form->handleRequest($request);

        // check form
        if ($form->isSubmitted() && $form->isValid()){
            // use app updater to set new values if they have changed
            if ($appConfigData->fileMode !== $appConfigData->existingFileMode){
                $appUpdater->setGitOption('core.filemode', $appConfigData->fileMode ? 'true' : 'false');
            }

            if ($appConfigData->sslVerify !== $appConfigData->existingSslVerify){
                $appUpdater->setGitOption('http.sslverify', $appConfigData->sslVerify ? 'true' : 'false');
            }

            if ($appConfigData->remoteOriginUrl !== $appConfigData->existingRemoteOriginUrl){
                $appUpdater->setGitOption('remote.origin.url', "\"$appConfigData->remoteOriginUrl\"");
            }

            // refresh page
            return $this->render('@JermBundle/Modal/notification.html.twig', array(
                'message' => 'Success!',
                'type' => 'success',
                'modal_size' => 'modal-sm',
                'full_refresh' => true,
                'fade' => true
            ));
        }

        // return form to user
        return $this->render('@JermBundle/Modal/app_config_form.html.twig', array(
            'header' => 'Configure Options',
            'form' => $form->createView(),
            'front_load' => ['bundles/jerm/js/appConfigForm.js']
        ));
    }
}