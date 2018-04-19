<?php

namespace Musicjerm\Bundle\JermBundle\Controller;

use Doctrine\DBAL\Schema\MySqlSchemaManager;
use Musicjerm\Bundle\JermBundle\Events\ImporterImportEvent;
use Musicjerm\Bundle\JermBundle\Model\CSVDataModel;
use Musicjerm\Bundle\JermBundle\Model\ImporterModel;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Yaml\Yaml;

class ImporterController extends Controller
{
    /** @var array */
    private $yamlConfig;

    /** @var array */
    private $importConfig;

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

        // set private array
        $this->yamlConfig = Yaml::parse(file_get_contents($configFile));

        // set class names
        $reflectionClass = $this->getDoctrine()->getManager()->getClassMetadata($this->yamlConfig['entity'])->getReflectionClass();
        $this->yamlConfig['entity_class'] = $reflectionClass->getName();
        $this->yamlConfig['entity_name'] = $reflectionClass->getShortName();

        // make sure import config is set
        if (!isset($this->yamlConfig['import'])){
            throw new \Exception('Import config is not set.');
        }

        // set import array from config file
        $this->importConfig = $this->yamlConfig['import'];
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param string $entity
     * @return Response
     * @throws \Exception
     */
    public function importAction(Request $request, UserInterface $user, $entity)
    {
        // set config array
        $this->setYamlConfig($entity);

        // check permissions set in config
        if (!$this->isGranted($this->yamlConfig['actions']['head']['jerm_bundle_importer_import']['role'])){
            throw new AccessDeniedException('You are not allowed here.');
        }

        // entity manager
        $em = $this->getDoctrine()->getManager();

        /** @var $sm MySqlSchemaManager */
        $sm = $this->get('database_connection')->getSchemaManager();
        $nameConverter = new CamelCaseToSnakeCaseNameConverter();
        $columnList = $sm->listTableColumns($nameConverter->normalize(lcfirst($this->yamlConfig['entity_name'])));

        // set column indexes
        $columnIndexes = array();
        foreach ($sm->listTableIndexes($nameConverter->normalize(lcfirst($this->yamlConfig['entity_name']))) as $index){
            if (!isset($columnIndexes[$index->getColumns()[0]])){
                $columnIndexes[$index->getColumns()[0]] = array();
            }
            if ($index->isPrimary()){
                $columnIndexes[$index->getColumns()[0]]['primary'] = true;
            }
            if ($index->isUnique()){
                $columnIndexes[$index->getColumns()[0]]['unique'] = true;
            }
        }

        // set foreign keys if any
        $columnFks = array();
        foreach ($sm->listTableForeignKeys($nameConverter->normalize(lcfirst($this->yamlConfig['entity_name']))) as $foreignKey){
            $columnFks[$nameConverter->denormalize($foreignKey->getLocalColumns()[0])] = array(
                'table'=>$foreignKey->getForeignTableName(),
                'column'=>$foreignKey->getForeignColumns()[0]
            );
        }

        // create array with new importer model for each column
        $structureArray = array();
        foreach ($this->importConfig['headers'] as $ic){
            $structureArray[$ic] = new ImporterModel();
            $structureArray[$ic]->setName(ucwords(str_replace('_', ' ', $nameConverter->normalize($ic))));
            if (isset($columnFks[$ic])){
                $structureArray[$ic]->setType('Entity');
                $structureArray[$ic]->setForeignKey($columnFks[$ic]);
                $structureArray[$ic]->setRepo($em->getRepository('AppBundle:'.ucfirst($nameConverter->denormalize($columnFks[$ic]['table']))));
            }else{
                $structureArray[$ic]->setType($columnList[$nameConverter->normalize($ic)]->getType());
            }
            $structureArray[$ic]->setRequired($columnList[$nameConverter->normalize($ic)]->getNotnull() ? true : false);
            if ($columnList[$nameConverter->normalize($ic)]->getLength()){
                $structureArray[$ic]->setLength($columnList[$nameConverter->normalize($ic)]->getLength());
            }
            $structureArray[$ic]->setPrimary(isset($columnIndexes[$nameConverter->normalize($ic)]['primary']) ? true : false);
            $structureArray[$ic]->setUnique(isset($columnIndexes[$nameConverter->normalize($ic)]['unique']) ? true : false);
        }

        // build form
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('jerm_bundle_importer_import', array('entity'=>$entity)))
            ->add('file', FileType::class, array(
                'label'=>'Select CSV File',
                'constraints'=>array(
                    new NotBlank(),
                    new File(array(
                        'maxSize'=>'100M',
                        'mimeTypes'=>'text/plain',
                        'mimeTypesMessage'=>'Please upload a valid CSV file.'
                    ))
                )))
            ->add('has_headers', CheckboxType::class, array('required'=>false))
            ->add('set_blanks_null', CheckboxType::class, array('required'=>false))
            ->getForm();

        // handle form request
        $form->handleRequest($request);

        // process data if form is submitted
        if ($form->isSubmitted() && $form->isValid()){
            set_time_limit(0);
            //truncate existing data
            if (isset($this->importConfig['truncate']) && $this->importConfig['truncate'] == true){
                $entityRepo = $em->getRepository($this->yamlConfig['entity']);
                $entities = $entityRepo->findAll();
                foreach ($entities as $objectToRemove){
                    $em->remove($objectToRemove);
                }
                $em->flush();
            }

            /** @var $file UploadedFile */
            $file = $request->files->get('form')['file'];
            $file_h = fopen($file->getRealPath(), "r");

            $batchSize = isset($this->importConfig['batch_size']) ? $this->importConfig['batch_size'] : 1000;
            $i = 0;
            $countNew = 0;
            $countUpdated = 0;
            while (($dataLine = fgetcsv($file_h,0,',')) !== false){
                if (isset($request->get('form')['has_headers']) && $i == 0){
                    //skip first line if headers exist
                }else{
                    if (count($dataLine) !== count($this->importConfig['headers'])){
                        return $this->render('@JermBundle/Modal/notification.html.twig', array(
                            'message'=>'Invalid Column Count.',
                            'type'=>'error'
                        ));
                    }

                    $search = array();
                    if (count($this->importConfig['keys']) > 0){
                        foreach ($this->importConfig['keys'] as $pos=>$importKey){
                            if (strlen($dataLine[$importKey]) > 0){
                                if ($structureArray[$this->importConfig['headers'][$importKey]]->getType() == 'Entity'){
                                    if (isset($this->importConfig['entity_names']) && isset($this->importConfig['entity_names'][$this->importConfig['headers'][$importKey]])){
                                        $searchEntity = 'AppBundle:'.$this->importConfig['entity_names'][$this->importConfig['headers'][$importKey]];
                                    }else{
                                        $searchEntity = 'AppBundle:'.ucfirst($this->importConfig['headers'][$importKey]);
                                    }
                                    $importKeyEntity = $em->find("$searchEntity", $dataLine[$importKey]);
                                    if (null !== $importKeyEntity){
                                        $search[$this->importConfig['headers'][$importKey]] = $importKeyEntity;
                                    }
                                }else{
                                    $search[$this->importConfig['headers'][$importKey]] = $dataLine[$importKey];
                                }
                            }
                        }
                        if (count($search) == count($this->importConfig['keys'])){
                            $workingObject = $em->getRepository($this->yamlConfig['entity'])->findOneBy($search);
                        }else{
                            $workingObject = null;
                        }
                    }else{
                        $workingObject = null;
                    }

                    $lineChanges = 0;
                    if (null === $workingObject && count($search) == count($this->importConfig['keys'])){
                        $workingObject = new $this->yamlConfig['entity_class'];
                        foreach ($this->importConfig['headers'] as $pos=>$headerKey){//parse entity setters
                            if (strlen($dataLine[$pos]) > 0){
                                switch($structureArray[$headerKey]->getType()){//type checking
                                    case 'Entity':
                                        $item = $structureArray[$headerKey]->getRepo()->findOneBy(array(
                                            isset($this->importConfig['entity_search'][$headerKey]) ? $this->importConfig['entity_search'][$headerKey] :
                                                $nameConverter->denormalize($structureArray[$headerKey]->getForeignKey()['column'])
                                            => $dataLine[$pos]
                                        ));
                                        $newValue = ($item ? $item : null);
                                        break;
                                    case 'Boolean':
                                        if (in_array(trim(strtolower($dataLine[$pos])), ['true', 'yes', '1'])){
                                            $newValue = 1;
                                        }else{
                                            $newValue = 0;
                                        }
                                        break;
                                    case 'Date':
                                        $newValue = new \DateTime($dataLine[$pos]);
                                        break;
                                    default:
                                        $newValue = trim($dataLine[$pos]);
                                }

                                $setter = 'set' . ucfirst($headerKey);
                                $workingObject->$setter($newValue);
                                $lineChanges++;
                            }
                        }
                        if ($lineChanges > 0){
                            // set user and date created if methods exist
                            !method_exists($workingObject, 'setUserCreated') ?: $workingObject->setUserCreated($user);
                            !method_exists($workingObject, 'setDateCreated') ?: $workingObject->setDateCreated(new \DateTime());
                            $countNew++;
                        }
                    }elseif(null !== $workingObject){
                        foreach ($this->importConfig['headers'] as $pos=>$headerKey){
                            if (strlen($dataLine[$pos]) > 0){
                                switch($structureArray[$headerKey]->getType()){//type checking
                                    case 'Entity':
                                        $item = $structureArray[$headerKey]->getRepo()->findOneBy(array(
                                            isset($this->importConfig['entity_search'][$headerKey]) ? $this->importConfig['entity_search'][$headerKey] :
                                                $nameConverter->denormalize($structureArray[$headerKey]->getForeignKey()['column'])
                                            => $dataLine[$pos]
                                        ));
                                        $newValue = ($item ? $item : null);
                                        break;
                                    case 'Boolean':
                                        if (in_array(trim(strtolower($dataLine[$pos])), ['true', 'yes', '1'])) {
                                            $newValue = 1;
                                        } else {
                                            $newValue = 0;
                                        }
                                        break;
                                    case 'Date':
                                        $newValue = new \DateTime($dataLine[$pos]);
                                        break;
                                    default:
                                        $newValue = trim($dataLine[$pos]);
                                }
                            }elseif(isset($request->get('form')['set_blanks_null'])){
                                $newValue = null;
                            }

                            if (array_key_exists('newValue', get_defined_vars())){
                                $getter = 'get' . ucfirst($headerKey);
                                if ($newValue != $workingObject->$getter()){
                                    $setter = 'set' . ucfirst($headerKey);
                                    $workingObject->$setter($newValue);
                                    $lineChanges++;
                                }

                                unset($newValue);
                            }
                        }
                        if ($lineChanges > 0){
                            $countUpdated++;
                        }
                    }

                    if ($lineChanges > 0){
                        // set user and date updated if methods exist
                        !method_exists($workingObject, 'setUserUpdated') ?: $workingObject->setUserUpdated($user);
                        !method_exists($workingObject, 'setDateUpdated') ?: $workingObject->setDateUpdated(new \DateTime());
                        $em->persist($workingObject);
                    }

                    if (($i % $batchSize) === 0){
                        $em->flush();
                        $em->clear($this->yamlConfig['entity']);
                    }

                    $workingObject = null;
                }
                $i++;
            }
            $em->flush();
            $em->clear($this->yamlConfig['entity']);
            fclose($file_h);

            $message = "Upload successful.  ($countNew) New items.  ($countUpdated) Updated items.";

            // dispatch event for logging
            if ($countNew > 0 || $countUpdated > 0){
                $event = new ImporterImportEvent(array(
                    'class' => $this->yamlConfig['entity_name'],
                    'new' => $countNew,
                    'updated' => $countUpdated
                ));
                $dispatcher = $this->get('event_dispatcher');
                $dispatcher->dispatch(ImporterImportEvent::NAME, $event);
            }

            return $this->render('@JermBundle/Modal/notification.html.twig', array(
                'message' => $message,
                'type' => 'success',
                'refresh' => true
            ));
        }

        if (isset($this->importConfig['entity_search'])){
            foreach ($this->importConfig['entity_search'] as $key=>$val){
                $structureArray[$key]->setForeignKey(array(
                        'table'=>$structureArray[$key]->getForeignKey()['table'],
                        'column'=>$val
                    )
                );
            }
        }

        return $this->render('@JermBundle/Modal/import_form.html.twig', array(
            'entity'=>$entity,
            'truncate'=>isset($this->importConfig['truncate']) ? $this->importConfig['truncate'] : false,
            'form'=>$form->createView(),
            'structure'=>$structureArray,
            'action'=>'create',
            'header'=>'Upload data for import (' . $this->yamlConfig['entity_name'] . ' Entity)'
        ));
    }

    /**
     * @param string $entity
     * @return Response
     * @throws \Exception
     */
    public function getTemplateAction($entity)
    {
        // set yaml config
        $this->setYamlConfig($entity);

        $nameConverter = new CamelCaseToSnakeCaseNameConverter();
        $dataHeaders = array();
        foreach ($this->yamlConfig['import']['headers'] as $value){
            $dataHeaders[] = ucwords(str_replace('_', ' ', $nameConverter->normalize($value)));
        }

        // build csv data
        $dumpModel = new CSVDataModel();
        $dumpModel->setColumnNames($dataHeaders);
        $dumpModel->setData(array());
        $dataDump = $dumpModel->buildCsv();

        // return to user
        $newFileName = getenv('app_name').'_'.ucfirst($entity).'_import_template.csv';
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$newFileName);
        $response->setContent($dataDump);
        return $response;
    }
}