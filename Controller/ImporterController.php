<?php

namespace Musicjerm\Bundle\JermBundle\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\MySqlSchemaManager;
use Doctrine\Persistence\ManagerRegistry;
use Musicjerm\Bundle\JermBundle\Form\Importer\ImporterUploadData;
use Musicjerm\Bundle\JermBundle\Form\Importer\ImporterUploadType;
use Musicjerm\Bundle\JermBundle\Message\FastImportMessage;
use Musicjerm\Bundle\JermBundle\Model\CSVDataModel;
use Musicjerm\Bundle\JermBundle\Model\ImporterStructureModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Yaml\Yaml;

class ImporterController extends AbstractController
{
    public function __construct(private readonly ManagerRegistry $doctrine) {}

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
        $reflectionClass = $this->doctrine->getManager()->getClassMetadata($this->yamlConfig['entity'])->getReflectionClass();
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
     * @param MessageBusInterface $bus
     * @param Connection $connection
     * @param Request $request
     * @param UserInterface $user
     * @param string $entity
     * @return Response
     * @throws \Exception
     */
    public function fastImport(MessageBusInterface $bus, Connection $connection, Request $request, UserInterface $user, string $entity): Response
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

        /** @var $sm MySqlSchemaManager */
        $sm = $connection->createSchemaManager();
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
        /** @var ImporterStructureModel[] $structureArray */
        $structureArray = array();
        foreach ($this->importConfig['headers'] as $key => $ic){
            $structureArray[$ic] = new ImporterStructureModel();
            $structureArray[$ic]->name = ucwords(str_replace('_', ' ', $nameConverter->normalize($ic)));
            if (isset($columnFks[$ic])){
                $structureArray[$ic]->type = 'Entity';
                $structureArray[$ic]->foreignKey = $columnFks[$ic];
                $structureArray[$ic]->repo = 'App\Entity\\'.ucfirst($nameConverter->denormalize($columnFks[$ic]['table']));
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
            // move uploaded file from temp location
            $newFileLocation = $this->getParameter('kernel.project_dir') . '/uploads/tmp/';
            $uploadFormData->file->move($newFileLocation);
            $newFilename = $newFileLocation . $uploadFormData->file->getFilename();

            // open file
            try {
                $fileHandler = fopen($newFilename, 'rb');
            } catch(\Exception $e){
                $processingErrors[] = array(
                    'code' => $e->getCode(),
                    'message' => $e->getMessage()
                );
            }

            // check headers, initialize query keys
            $headerRow = fgetcsv($fileHandler, 0);
            $queryKeys = array();
            $updateOnly = false;
            // loop structure array, set positions
            foreach ($structureArray as $key => $columnHeader){
                $positionArray = array_keys($headerRow, $columnHeader->name);

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

            // close file
            fclose($fileHandler);

            // create message and dispatch for processing if no errors found at this point
            if ($headerErrors === false && count($processingErrors) === 0){
                // create message object
                $message = new FastImportMessage();
                $message
                    ->setUserId($user->getId())
                    ->setEntityClass($this->yamlConfig['entity_class'])
                    ->setImportConfig($this->importConfig)
                    ->setFilePath($newFilename)
                    ->setStructure($structureArray)
                    ->setPageName($this->yamlConfig['page_name'])
                    ->setQueryKeys($queryKeys)
                    ->setUpdateOnly($updateOnly);

                // dispatch message to process data
                $envelope = new Envelope($message);
                $bus->dispatch($envelope);

                return $this->render('@Jerm/Modal/notification.html.twig', array(
                    'message' => 'Upload is being processed, please check logs for status',
                    'type' => 'warning'
                ));
                //todo: how to notify user of errors / status / completion?
            }

            // remove uploaded file
            $fs = new Filesystem();
            $fs->remove($newFilename);
        }

        // display form to user
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