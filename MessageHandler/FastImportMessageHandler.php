<?php

namespace Musicjerm\Bundle\JermBundle\MessageHandler;

use App\Entity\User;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Musicjerm\Bundle\JermBundle\Entity\Notification;
use Musicjerm\Bundle\JermBundle\Message\FastImportMessage;
use Musicjerm\Bundle\JermBundle\Model\ImporterStructureModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class FastImportMessageHandler implements MessageHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /** @throws Exception */
    public function __invoke(FastImportMessage $fim)
    {
        // create a timer
        $sw = new Stopwatch();
        $sw->start('timer');

        // open file
        try {
            $fileHandler = fopen($fim->getFilePath(), 'rb');
        } catch(\Exception $e){
            $this->logger?->error(sprintf('Exception: %s', $e->getMessage()));
            return;
        }

        // set batch size to keep memory usage down
        $batchSize = $this->importConfig['batch_size'] ?? 1000;

        // query the user, set repo
        $user = $this->em->getRepository(User::class)->find($fim->getUserId());
        $repo = $this->em->getRepository($fim->getEntityClass());

        // check for and execute pre-process query
        if (method_exists($repo, 'preFastImport')){
            $repo->preFastImport();
        }

        // set sub repositories for querying foreign keys
        /** @var ImporterStructureModel $ism */
        foreach ($fim->getStructure() as $key => $ism){
            if ($ism->repo !== null){
                $fim->getStructure()[$key]->repo = $this->em->getRepository($ism->repo);
            }
        }

        // set some vars
        $i = 0;
        $newCount = 0;
        $updateCount = 0;
        $processingErrors = array();
        $entityClass = $fim->getEntityClass();
        $transformer = $fim->getImportConfig()['transformer'];
        $actionLogClass = 'App\Entity\ActionLog';

        // skip header row
        fgetcsv($fileHandler, 0);

        // loop csv rows
        while (($row = fgetcsv($fileHandler, 0)) !== false){

            $queryArray = array();
            foreach ($fim->getQueryKeys() as $key => $value){
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
            if ($workingObject === null && $fim->isUpdateOnly() === false){
                $workingObject = new $entityClass();
                $persist = true;
            }else{
                $persist = false;
            }

            // transform values
            if ($workingObject !== null){
                $transformer = new $transformer(
                    $workingObject,
                    $user,
                    $row,
                    $fim->getStructure()
                );

                if (method_exists($transformer, 'importerFastTransformer')){
                    $transformer->importerFastTransformer();
                }

                // persist, count
                if ($persist === true){
                    try{
                        $this->em->persist($workingObject);
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
                    $this->em->flush();
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

            $i++;
        }

        // check for processing errors so far
        if ($processingErrors === []){
            // save remaining database changes
            try{
                $this->em->flush();
            }catch(\Exception $e){
                $processingErrors[] = array(
                    'code' => $e->getCode(),
                    'message' => $e->getMessage()
                );
            }
        }

        // close file
        fclose($fileHandler);

        // delete file
        if (is_file($fim->getFilePath())){
            $fs = new Filesystem();
            $fs->remove($fim->getFilePath());
        }

        // post-processing
        if ($processingErrors === [] && method_exists($repo, 'postFastImport')){
            $repo->postFastImport();
        }

        // stop timer
        $timer = $sw->stop('timer');
        $secondsElapsed = $timer->getDuration() / 1000;
        $megabytesUsed = $timer->getMemory() / 1000000;

        // log errors
        $errorCount = count($processingErrors);
        foreach ($processingErrors as $error){
            $errorMessage = sprintf('%s import Error code: %s - %s', $fim->getPageName(), $error['code'], $error['message']);
            $this->logger->error($errorMessage);
            if (class_exists($actionLogClass)){
                $this->em->persist((new $actionLogClass())
                    ->setUserCreated($user)
                    ->setAction('Import')
                    ->setDetail($errorMessage)
                );
            }
        }

        // count errors
        if ($errorCount > 0){
            $newCount = 0;
            $updateCount = 0;
        }

        // message
        $message = sprintf(
            '%s import completed in %s seconds, %sMB used.  (%s) errors occurred.  (%s) new and (%s) updated',
            $fim->getPageName(), $secondsElapsed, $megabytesUsed, $errorCount, $newCount, $updateCount);

        // log
        $this->logger->notice($message);
        if (class_exists($actionLogClass)){
            $this->em->persist((new $actionLogClass())
                ->setUserCreated($user)
                ->setAction('Import')
                ->setDetail($message)
            );
        }

        // notify user
        $notification = new Notification();
        $notification
            ->setUser($user)
            ->setSubject(sprintf('%s import processed', $fim->getPageName()))
            ->setClass('info')
            ->setMessage($message)
            ->setUnread(1)
            ->setDate(new \DateTime());

        $this->em->persist($notification);
        $this->em->flush();

        // todo: how to notify user of errors and / or completion?  mercure?

    }
}