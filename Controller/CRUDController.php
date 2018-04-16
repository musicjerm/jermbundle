<?php

namespace Musicjerm\Bundle\JermBundle\Controller;

use App\Entity\User;
use Musicjerm\Bundle\JermBundle\Events\CrudCreateEvent;
use Musicjerm\Bundle\JermBundle\Events\CrudUpdateEvent;
use Musicjerm\Bundle\JermBundle\Events\CrudDeleteEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Yaml\Yaml;

class CRUDController extends Controller
{
    /**
     * Configuration for DataTables loaded entities
     * Store config files in /src/JBConfig/Entity/
     */
    private $yamlConfig;

    /**
     * @var boolean $locationRestricted
     */
    private $locationRestricted;

    /**
     * @var string $fileSavePath
     */
    private $fileSavePath;

    /**
     * @param string $entity
     * @param string $id
     */
    public function setFileSavePath($entity, $id = null)
    {
        $this->fileSavePath = $this->getParameter('kernel.project_dir')."/uploads/$entity/";
        !$id ?: $this->fileSavePath .= "$id/";
    }

    /**
     * @param string $configName
     * @throws \Exception
     */
    private function setYamlConfig($configName)
    {
        $configDirs = array(
            $this->getParameter('kernel.root_dir') . '/JBConfig/Entity',
            $this->getParameter('kernel.project_dir') . '/vendor/musicjerm/jermbundle/Resources/config/Entity'
        );

        $configFile = null;
        foreach ($configDirs as $dir){
            if (file_exists($dir . "/$configName.yaml")){
                $configFile = $dir . "/$configName.yaml";
                break;
            }elseif (file_exists($dir . "/$configName.yml")){
                $configFile = $dir . "/$configName.yml";
                break;
            }
        }

        if ($configFile === null){
            throw new \Exception('JB Entity config file is missing.', 500);
        }

        $this->yamlConfig = Yaml::parse(file_get_contents($configFile));
    }

    /**
     * @param string $actionType
     * @param string $actionName
     * @param User $user
     * @param null $object
     */
    private function checkPermissions($actionType, $actionName, $user, $object = null)
    {
        if (!$this->isGranted($this->yamlConfig['actions'][$actionType][$actionName]['role'])){
            throw new AccessDeniedException();
        }

        if (isset($this->yamlConfig['actions'][$actionType][$actionName]['restrict_owner'])){
            if ($object && method_exists($object, 'getUserCreated')){
                if ($user !== $object->getUserCreated()){
                    throw new AccessDeniedException();
                }
            }
        }

        if (isset($this->yamlConfig['actions'][$actionType][$actionName]['restrict_location']) && method_exists($user, 'getLocation')){
            $this->locationRestricted = true;
            if ($object && method_exists($object, 'getLocation')){
                if ($user->getLocation() !== $object->getLocation()){
                    throw new AccessDeniedException();
                }
            }else{
                if ($user->getLocation() === null){
                    throw new AccessDeniedException();
                }
            }
        }
    }

    /**
     * @param Request $request
     * @param UserInterface|User $user
     * @param string $entity
     * @return Response
     * @throws \Exception
     */
    public function createAction(Request $request, UserInterface $user, $entity)
    {
        // set yaml config
        $this->setYamlConfig($entity);

        // check permissions
        $this->checkPermissions('head', 'jerm_bundle_crud_create', $user);

        // create new working object
        $entityClass = str_replace(':', '\Entity\\', $this->yamlConfig['entity']);
        $workingObject = new $entityClass;
        $reflect = new \ReflectionClass($workingObject);
        $entityClassName = $reflect->getShortName();

        // check for form class
        $normalizer = new CamelCaseToSnakeCaseNameConverter();
        $formTypeClass = "App\Form\CRUD\\" . ucfirst($normalizer->denormalize($entity)) . 'Type';

        if (!class_exists($formTypeClass)){
            throw new \Exception($formTypeClass.' is missing.');
        }

        // create form
        $form = $this->createForm(
            $formTypeClass,
            $workingObject,
            array(
                'action' => $this->generateUrl('jerm_bundle_crud_create', ['entity' => $entity])
            ));

        $form->handleRequest($request);

        // if form is not submitted or valid, render form
        if (!$form->isSubmitted() || !$form->isValid()){
            $frontLoadFiles = array();
            if (isset($this->yamlConfig['actions']['head']['jerm_bundle_crud_create']['front_load'])){
                foreach ($this->yamlConfig['actions']['head']['jerm_bundle_crud_create']['front_load'] as $jsFile){
                    $frontLoadFiles[] = $jsFile;
                }
            }

            return $this->render('@JermBundle/Modal/form.html.twig', array(
                'header' => 'Create New '.ucfirst(str_replace('_', ' ', $entity)),
                'form' => $form->createView(),
                'front_load' => $frontLoadFiles
            ));
        }

        // check for submitted files
        if (method_exists($workingObject, 'setDocument') && method_exists($workingObject, 'getFile') && $workingObject->getFile()){
            $workingObject->setDocument($workingObject->getFile()->getClientOriginalName());
            $this->setFileSavePath($entityClassName);
        }

        // set location if necessary
        if ($this->locationRestricted && method_exists($workingObject, 'setLocation') && method_exists($user, 'getLocation')){
            $workingObject->setLocation($user->getLocation());
        }

        // persist object
        $em = $this->getDoctrine()->getManager();
        $em->persist($workingObject);
        $em->flush();

        // save submitted file with new entity ID
        if (method_exists($workingObject, 'getId') && $workingObject->getId()){
            if (method_exists($workingObject, 'setDocument') && method_exists($workingObject, 'getFile') && $workingObject->getFile()){
                $fileSavePath = $this->fileSavePath.$workingObject->getId();
                $workingObject->getFile()->move($fileSavePath, $workingObject->getFile()->getClientOriginalName());
            }
        }

        // dispatch event for logging, etc
        $event = new CrudCreateEvent($workingObject);
        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch(CrudCreateEvent::NAME, $event);

        // render success notification
        return $this->render('@JermBundle/Modal/notification.html.twig', array(
            'modal_size' => 'modal-sm',
            'message' => 'Success!',
            'type' => 'success',
            'refresh' => true,
            'fade' => true
        ));
    }

    /**
     * @param Request $request
     * @param UserInterface|User $user
     * @param string $entity
     * @param string $id
     * @return Response
     * @throws \Exception
     */
    public function updateAction(Request $request, UserInterface $user, $entity, $id)
    {
        // set yaml config
        $this->setYamlConfig($entity);

        // query object
        $id = urldecode($id);
        $em = $this->getDoctrine()->getManager();
        $entityClass = $this->yamlConfig['entity'];
        $workingObject = $em->find($entityClass, $id);
        $reflect = new \ReflectionClass($workingObject);
        $entityClassName = $reflect->getShortName();

        if (!$workingObject){
            throw new \Exception('Could not find object');
        }

        // check permissions
        $this->checkPermissions('item', 'jerm_bundle_crud_update', $user, $workingObject);

        // check for form class
        $normalizer = new CamelCaseToSnakeCaseNameConverter();
        $formTypeClass = "App\Form\CRUD\\".ucfirst($normalizer->denormalize($entity)).'Type';

        if (!class_exists($formTypeClass)){
            throw new \Exception($formTypeClass.' is missing.');
        }

        // create form
        $form = $this->createForm(
            $formTypeClass,
            $workingObject,
            array(
                'action' => $this->generateUrl('jerm_bundle_crud_update', ['entity' => $entity, 'id' => urlencode($id)])
            ));

        $form->handleRequest($request);

        // if form is not submitted or valid, render form
        if (!$form->isSubmitted() || !$form->isValid()){
            $frontLoadFiles = array();
            if (isset($this->yamlConfig['actions']['item']['jerm_bundle_crud_update']['front_load'])){
                foreach ($this->yamlConfig['actions']['item']['jerm_bundle_crud_update']['front_load'] as $jsFile){
                    $frontLoadFiles[] = $jsFile;
                }
            }

            return $this->render('@JermBundle/Modal/form.html.twig', array(
                'header' => 'Update '.ucfirst(str_replace('_', ' ', $entity))." $id",
                'form' => $form->createView(),
                'front_load' => $frontLoadFiles
            ));
        }

        // throw error if trying to change the ID
        if ($workingObject->getId() != $id){
            throw new \Exception("Cannot change the $entity ID.");
        }

        // check for submitted files, delete old, save new
        if (method_exists($workingObject, 'setDocument') && method_exists($workingObject, 'getFile') && $workingObject->getFile()){
            $workingObject->setDocument($workingObject->getFile()->getClientOriginalName());
            $this->setFileSavePath($entityClassName, $workingObject->getId());
            $fs = new Filesystem();
            $fs->remove($this->fileSavePath);
            $workingObject->getFile()->move($this->fileSavePath, $workingObject->getDocument());
        }

        // flush database
        $em->flush();

        // dispatch event for logging, etc
        $event = new CrudUpdateEvent($workingObject);
        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch(CrudUpdateEvent::NAME, $event);

        // render success notification
        return $this->render('@JermBundle/Modal/notification.html.twig', array(
            'modal_size' => 'modal-sm',
            'message' => 'Success!',
            'type' => 'success',
            'refresh' => true,
            'fade' => true
        ));
    }

    /**
     * @param Request $request
     * @param UserInterface|User $user
     * @param string $entity
     * @return Response
     * @throws \Exception
     */
    public function deleteAction(Request $request, UserInterface $user, $entity)
    {
        // set yaml config
        $this->setYamlConfig($entity);
        $ids = $request->get('id') ? $request->get('id') : $request->get('form')['id'];
        $ids ?: $ids = [];
        $em = $this->getDoctrine()->getManager();

        // set any constraints that might exist
        $constraints = array();
        if (isset($this->yamlConfig['actions']['group']['jerm_bundle_crud_delete']['constraints'])){
            foreach ($this->yamlConfig['actions']['group']['jerm_bundle_crud_delete']['constraints'] as $constraint){
                $constraints[] = array(
                    'repo' => $em->getRepository($constraint['entity']),
                    'field' => $constraint['field']
                );
            }
        }

        // create new unused object
        $entityClass = str_replace(':', '\Entity\\', $this->yamlConfig['entity']);
        $unusedObject = new $entityClass;
        $reflect = new \ReflectionClass($unusedObject);
        $entityClassName = $reflect->getShortName();

        // loop and query objects
        $deleteCount = 0;
        $deleteArray = array();
        foreach ($ids as $id){
            $entityClass = $this->yamlConfig['entity'];
            $workingObject = $em->find($entityClass, $id);

            if (!$workingObject){
                throw new \Exception('Entity not found.');
            }

            // check permissions
            $this->checkPermissions('group', 'jerm_bundle_crud_delete', $user, $workingObject);

            // populate array with objects and titles
            $deleteArray[$id] = array(
                'object' => $workingObject,
                'string' => method_exists($workingObject, '__toString') ? $workingObject->__toString() : "$entityClassName $id",
                'delete' => true
            );

            // check for constraints
            foreach ($constraints as $constraint){
                /** @var array $constraint */
                if ($constraint['repo']->findBy([$constraint['field'] => $id])){
                    $deleteArray[$id]['delete'] = false;
                }
            }

            // count deletable
            !$deleteArray[$id]['delete'] ?: $deleteCount++;
        }

        // build form
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('jerm_bundle_crud_delete', ['entity' => $entity]))
            ->add('id', CollectionType::class, array(
                'entry_type' => HiddenType::class,
                'data' => $ids,
                'label' => false
            ))->getForm();

        $form->handleRequest($request);

        // return form if hasn't been submitted or isn't valid
        if (!$form->isSubmitted() || !$form->isValid()){
            return $this->render('@JermBundle/Modal/delete_form.html.twig', array(
                'header' => 'Delete selected '.ucfirst(str_replace('_', ' ', $entity)).' item(s)?',
                'form' => $form->createView(),
                'delete_array' => $deleteArray,
                'delete_count' => $deleteCount
            ));
        }

        // loop array, remove objects and files
        $countRemoved = 0;
        $objectStrings = array();
        foreach ($deleteArray as $key => $item){
            if ($item['delete']){
                // check for files and delete them
                $this->setFileSavePath($entityClassName, $key);
                $fs = new Filesystem();
                $fs->remove($this->fileSavePath);

                // get object string for logging
                $string = '';
                !method_exists($item['object'], 'getId') ?: $string .= '('.$item['object']->getId().')';
                !method_exists($item['object'], '__toString') ?: $string .= ' - '.$item['object']->__toString();
                strlen($string) < 1 ?: $objectStrings[] = $string;

                // remove object
                $em->remove($item['object']);
                $countRemoved++;
            }
        }

        // flush database
        if ($countRemoved < 1){
            return $this->render('@JermBundle/Modal/notification.html.twig', array(
                'modal_size' => 'modal-sm',
                'message' => "No items removed.",
                'type' => 'info',
                'refresh' => true,
                'fade' => true
            ));
        }

        $em->flush();

        // dispatch event for logging, etc
        $event = new CrudDeleteEvent(array(
            'class' => $entityClassName,
            'deleted' => $objectStrings
        ));
        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch(CrudDeleteEvent::NAME, $event);

        // render success notification
        return $this->render('@JermBundle/Modal/notification.html.twig', array(
            'modal_size' => 'modal-sm',
            'message' => "Success! ($countRemoved) items removed.",
            'type' => 'success',
            'refresh' => true,
            'fade' => true
        ));
    }
}