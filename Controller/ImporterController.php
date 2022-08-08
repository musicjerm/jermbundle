<?php

namespace Musicjerm\Bundle\JermBundle\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\MySqlSchemaManager;
use Musicjerm\Bundle\JermBundle\Events\ImporterImportEvent;
use Musicjerm\Bundle\JermBundle\Form\Importer\ImporterUploadData;
use Musicjerm\Bundle\JermBundle\Form\Importer\ImporterUploadType;
use Musicjerm\Bundle\JermBundle\Model\CSVDataModel;
use Musicjerm\Bundle\JermBundle\Model\ImporterStructureModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Yaml\Yaml;

class ImporterController extends AbstractController
{
    /** @var array */
    private $yamlConfig;

    /** @var array */
    private $importConfig;

    /**
     * @param string $configName
     * @return bool
     * @throws \Exception
     */
    private function setYamlConfig($configName): bool
    {
        $configDirs = array(
            $this->getParameter('kernel.project_dir') . '/src/JBConfig/Entity',
            $this->getParameter('kernel.project_dir') . '/vendor/musicjerm/jermbundle/Resources/config/Entity'
        );

        $configFile = null;
        foreach ($configDirs as $dir){
            if (file_exists($dir . "/$configName.yaml")){
                $configFile = $dir . "/$configName.yaml";
                break;
            }

            if (file_exists($dir . "/$configName.yml")){
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

        return true;
    }

    private $transformerError;

    private function checkTransformer(): bool
    {
        if (!array_key_exists('transformer', $this->importConfig)){
            $this->transformerError = 'Transformer not defined in config';
            return false;
        }

        if (!class_exists($this->importConfig['transformer'])){
            $this->transformerError = 'Transformer class could not be found';
            return false;
        }

        if (!method_exists($this->importConfig['transformer'], 'importerFastTransformer')){
            $this->transformerError = 'Method "importerFastTransformer" does not exist in transformer class';
            return false;
        }

        return true;
    }

    /**
     * @param Connection $connection
     * @param EventDispatcherInterface $dispatcher
     * @param Request $request
     * @param UserInterface $user
     * @param string $entity
     * @return Response
     * @throws \Exception
     */
    public function fastImport(Connection $connection, EventDispatcherInterface $dispatcher, Request $request, UserInterface $user, $entity): Response
    {
        // set yaml config
        $this->setYamlConfig($entity);

        // make sure transformer is configured
        if ($this->checkTransformer() === false){
            return $this->render('@Jerm/Modal/notification.html.twig', array(
                'type' => 'error',
                'message' => $this->transformerError
            ));
        }

        // entity manager
        $em = $this->getDoctrine()->getManager();

        /** @var $sm MySqlSchemaManager */
        $sm = $connection->createSchemaManager();
        $nameConverter = new CamelCaseToSnakeCaseNameConverter();
        $columnList = $sm->listTableColumns($nameConverter->normalize(lcfirst($this->yamlConfig['entity_name'])));// create array with new importer model for each column

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

        /** @var ImporterStructureModel[] $structureArray */
        $structureArray = array();
        foreach ($this->importConfig['headers'] as $key => $ic){
            $structureArray[$ic] = new ImporterStructureModel();
            $structureArray[$ic]->name = ucwords(str_replace('_', ' ', $nameConverter->normalize($ic)));
            if (isset($columnFks[$ic])){
                $structureArray[$ic]->type = 'Entity';
                $structureArray[$ic]->foreignKey = $columnFks[$ic];
                $structureArray[$ic]->repo = $em->getRepository('App\Entity\\'.ucfirst($nameConverter->denormalize($columnFks[$ic]['table'])));
            }else{
                $structureArray[$ic]->type = $columnList[$nameConverter->normalize($ic)]->getType()->getName();
            }
            $structureArray[$ic]->required = $columnList[$nameConverter->normalize($ic)]->getNotnull();
            if ($columnList[$nameConverter->normalize($ic)]->getLength()){
                $structureArray[$ic]->length = $columnList[$nameConverter->normalize($ic)]->getLength();
            }
            $structureArray[$ic]->primary = isset($columnIndexes[$nameConverter->normalize($ic)]['primary']);
            $structureArray[$ic]->unique = isset($columnIndexes[$nameConverter->normalize($ic)]['unique']);

            if (\in_array($key, $this->importConfig['keys'], true)){
                $structureArray[$ic]->primary = true;
                $structureArray[$ic]->required = true;
            }
        }

        // build form
        $uploadFormData = new ImporterUploadData();
        $uploadForm = $this->createForm(ImporterUploadType::class, $uploadFormData, array(
            'action' => $this->generateUrl('jerm_bundle_importer_fast', ['entity' => $entity])
        ));

        // process form
        $uploadForm->handleRequest($request);

        // check form
        $headerErrors = false;
        $processingErrors = array();
        if ($uploadForm->isSubmitted() && $uploadForm->isValid()){
            // capture start time, remove time limit
            $startTime = new \DateTime();
            set_time_limit(0);

            // set batch size to keep memory usage down
            $batchSize = $this->importConfig['batch_size'] ?? 1000;
            $updateOnly = false;

            // repo
            $repo = $em->getRepository($this->yamlConfig['entity_class']);

            // check for pre-process script, execute
            if (method_exists($repo, 'preFastImport')){
                $repo->preFastImport();
            }

            // open file
            $fileHandler = fopen($uploadFormData->file->getRealPath(), 'rb');

            // loop lines
            $i = 0;
            $newCount = 0;
            $updateCount = 0;
            $queryKeys = array();
            while (($row = fgetcsv($fileHandler, 0, ',')) !== false){
                // check headers
                if ($i === 0){
                    // loop structure array, set positions
                    foreach ($structureArray as $key => $columnHeader){
                        $positionArray = array_keys($row, $columnHeader->name);

                        // check for duplicate headers
                        if (\count($positionArray) > 1){
                            $columnHeader->error[] = 'Duplicate column headers';
                            $headerErrors = true;
                        }

                        // check for primary header
                        if ($columnHeader->primary === true && \count($positionArray) === 0){
                            $columnHeader->error[] = 'Primary header missing';
                            $headerErrors = true;
                        }elseif ($columnHeader->primary === true){
                            // set query keys
                            $queryKeys[$key] = $positionArray[0];
                        }

                        // check for required, but non-primary header
                        if ($columnHeader->primary === false && $columnHeader->required === true && \count($positionArray) === 0){
                            $columnHeader->warning[] = 'Required header missing, update only enforced';
                            $updateOnly = true;
                        }

                        // set position
                        if (\count($positionArray) > 0){
                            $columnHeader->position = $positionArray[0];
                        }
                    }

                    // do not proceed if errors
                    if ($headerErrors === true){break;}
                }else{

                    $queryArray = array();
                    foreach ($queryKeys as $key => $value){
                        $queryArray[$key] = $row[$value];
                    }

                    // query existing object
                    if (method_exists($repo, 'fastImportQuery')){
                        $workingObject = $repo->fastImportQuery($queryArray);
                    }elseif(\count($queryArray) > 0){
                        $workingObject = $repo->findOneBy($queryArray);
                    }else{
                        $workingObject = null;
                    }

                    // create new object
                    if ($workingObject === null && $updateOnly === false){
                        $workingObject = new $this->yamlConfig['entity_class']();
                        $persist = true;
                    }else{
                        $persist = false;
                    }

                    // transform values
                    if ($workingObject !== null){
                        $transformer = new $this->importConfig['transformer'](
                            $workingObject,
                            $user,
                            $row,
                            $structureArray
                        );

                        method_exists($transformer, 'importerFastTransformer') ? $transformer->importerFastTransformer() : false;

                        // persist, count
                        if ($persist === true){
                            try{
                                $em->persist($workingObject);
                                $newCount++;
                            }catch(\Exception $e){
                                $processingErrors[] = array(
                                    'code' => $e->getCode(),
                                    'message' => $e->getMessage()
                                );
                            }
                        }else{
                            $updateCount++;
                        }
                    }

                    // save database changes if batch complete
                    if (($i % $batchSize) === 0){
                        try{
                            $em->flush();
                        }catch(\Exception $e){
                            $processingErrors[] = array(
                                'code' => $e->getCode(),
                                'message' => $e->getMessage()
                            );
                        }
                    }

                    // if errors, stop the loop
                    if (\count($processingErrors) > 0){
                        break;
                    }
                }

                $i++;
            }

            // check for processing errors so far
            if ($processingErrors === []){
                // save remaining database changes
                try{
                    $em->flush();
                }catch(\Exception $e){
                    $processingErrors[] = array(
                        'code' => $e->getCode(),
                        'message' => $e->getMessage()
                    );
                }
            }

            // close file
            fclose($fileHandler);

            // check for errors
            if ($headerErrors === false && $processingErrors === []){

                // check for post-process script, execute
                if (method_exists($repo, 'postFastImport')){
                    $repo->postFastImport();
                }

                // capture finish time, set total time and message strings
                $endTime = new \DateTime();
                $totalTimeString = $startTime->diff($endTime)->format('%H:%I:%S');
                $message = $this->yamlConfig['entity_name'];
                $message .= " Import completed in ($totalTimeString).  ($newCount) new, ($updateCount) updated.";

                // dispatch event for logging
                $event = new ImporterImportEvent(array(
                    'class' => $this->yamlConfig['entity_name'],
                    'new' => $newCount,
                    'updated' => $updateCount
                ));

                $dispatcher->dispatch($event);

                // return success message to user
                return $this->render('@JermBundle/Modal/notification.html.twig', array(
                    'message' => $message,
                    'refresh' => true
                ));
            }
        }

        return $this->render('@Jerm/importer/modal_form_fast_importer.html.twig', array(
            'header' => 'Upload data for bulk create/update (' . $this->yamlConfig['page_name']. ')',
            'form' => $uploadForm->createView(),
            'structure' => $structureArray,
            'header_errors' => $headerErrors,
            'processing_errors' => $processingErrors,
            'get_template_url' => $this->generateUrl('jerm_bundle_importer_get_template', ['entity' => $entity])
        ));
    }

    /**
     * @param string $entity
     * @return Response
     * @throws \Exception
     */
    public function getTemplateAction($entity): Response
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
        $newFileName = $this->getParameter('app_name').'_'.ucfirst($entity).'_import_template.csv';
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$newFileName);
        $response->setContent($dataDump);
        return $response;
    }
}