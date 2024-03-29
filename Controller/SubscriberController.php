<?php

namespace Musicjerm\Bundle\JermBundle\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Musicjerm\Bundle\JermBundle\Entity\Subscriber;
use Musicjerm\Bundle\JermBundle\Events\SubscriberBatchEvent;
use Musicjerm\Bundle\JermBundle\Events\SubscriberCreateEvent;
use Musicjerm\Bundle\JermBundle\Form\BatchSubscriberModel;
use Musicjerm\Bundle\JermBundle\Form\BatchSubscriberType;
use Musicjerm\Bundle\JermBundle\Form\CreateSubscriberType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

class SubscriberController extends AbstractController
{
    public function __construct(private readonly ManagerRegistry $doctrine) {}

    /**
     * @Route("/subscriber/create/{entity}/{id}", name="jerm_bundle_subscriber_create")
     * @param string $entity
     * @param string $id
     * @param Request $request
     * @param UserInterface|User $user
     * @param EventDispatcherInterface $dispatcher
     * @return Response
     * @throws \Exception
     */
    public function createAction($entity, $id, Request $request, UserInterface $user, EventDispatcherInterface $dispatcher): Response
    {
        $nameConverter = new CamelCaseToSnakeCaseNameConverter();
        $entityRepoName = 'App\Entity\\' . ucfirst($nameConverter->denormalize($entity));

        /** @var Subscriber $subscription */
        $subscriptionRepo = $this->doctrine->getRepository('Musicjerm\Bundle\JermBundle\Entity\Subscriber');
        $subscription = $subscriptionRepo->findOneBy(array(
            'entity' => $entity,
            'entityId' => $request->get('id'),
            'user' => $user
        ));

        if ($subscription === null){
            $entityRepo = $this->doctrine->getRepository($entityRepoName);
            $entityLine = $entityRepo->find($request->get('id'));

            if ($entityLine === null){
                throw new \Exception('Could not find entity for subscriber');
            }

            $subscription = new Subscriber();
            $subscription
                ->setEntity($entity)
                ->setEntityId($entityLine->getId())
                ->setDescription($entityLine->__toString())
                ->setUser($user)
                ->setEmail(false)
                ->setSystem(false)
                ->setUserCreated($user);

            $successMessage = 'Subscription created';
        }else{
            $successMessage = 'Subscription updated';
        }

        $form = $this->createForm(CreateSubscriberType::class, $subscription, array(
            'action' => $this->generateUrl('jerm_bundle_subscriber_create', ['entity' => $entity, 'id' => $id])
        ));

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()){
            return $this->render('@JermBundle/Modal/form.html.twig', array(
                'header' => 'Subscribe to the selected line',
                'form' => $form->createView()
            ));
        }

        $subscription
            ->setUserUpdated($user);

        $em = $this->doctrine->getManager();
        $em->persist($subscription);
        $em->flush();

        // dispatch event for logging
        $event = new SubscriberCreateEvent($subscription);
        $dispatcher->dispatch($event);

        return $this->render('@JermBundle/Modal/notification.html.twig', array(
            'message' => $successMessage,
            'modal_size' => 'modal-sm',
            'type' => 'success',
            'fade' => true
        ));
    }

    /**
     * @Route("/subscriber/batch/{entity}", name="jerm_bundle_subscriber_batch")
     * @param EventDispatcherInterface $dispatcher
     * @param Request $request
     * @param UserInterface|User $user
     * @param string $entity
     * @return Response
     * @throws \Exception
     */
    public function batchAction(EventDispatcherInterface $dispatcher, Request $request, UserInterface $user, $entity): Response
    {
        $ids = $request->get('id') ?: $request->get('batch_subscriber')['id'];
        $nameConverter = new CamelCaseToSnakeCaseNameConverter();
        $entityRepoName = 'App\Entity\\' . ucfirst($nameConverter->denormalize($entity));
        $entityRepo = $this->doctrine->getRepository($entityRepoName);
        $entityLines = array();

        foreach ((array) $ids as $id){
            $entityLines[$id] = $entityRepo->find($id)->__toString();
        }

        $batchSubscriberModel = new BatchSubscriberModel();
        $batchSubscriberModel
            ->setId($ids)
            ->setUsers([$user])
            ->setEmail(false)
            ->setSystem(false);

        $form = $this->createForm(BatchSubscriberType::class, $batchSubscriberModel, array(
            'action' => $this->generateUrl('jerm_bundle_subscriber_batch', ['entity' => $entity]),
            'is_manager' => $this->isGranted('ROLE_MANAGER')
        ));

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()){
            return $this->render('@JermBundle/Modal/batch_subscriber.html.twig', array(
                'header' => 'Configure Subscribers',
                'form' => $form->createView(),
                'entity_lines' => $entityLines
            ));
        }

        $em = $this->doctrine->getManager();
        $subscriberRepo = $em->getRepository('Musicjerm\Bundle\JermBundle\Entity\Subscriber');

        $newSubscriberCount = 0;
        $subbedUsers = array();

        foreach ($batchSubscriberModel->getUsers() as $subUser){
            foreach ($entityLines as $id => $entityString){
                if (!$subscriberRepo->findBy(['entity' => $entity, 'entityId' => $id, 'user' => $subUser])){
                    $newSubscriber = new Subscriber();
                    $newSubscriber
                        ->setEntity($entity)
                        ->setEntityId($id)
                        ->setUser($subUser)
                        ->setEmail($batchSubscriberModel->getEmail())
                        ->setSystem($batchSubscriberModel->getSystem())
                        ->setDescription($entityString)
                        ->setUserCreated($user)
                        ->setUserUpdated($user);

                    $em->persist($newSubscriber);
                    $newSubscriberCount++;
                    if (!\in_array($subUser->getUsername(), $subbedUsers)){
                        $subbedUsers[] = $subUser->getUsername();
                    }
                }
            }
        }

        // flush database
        $em->flush();

        // dispatch event for logging
        $event = new SubscriberBatchEvent(array(
            'subbed_users' => $subbedUsers,
            'sub_count' => $newSubscriberCount,
            'entity' => $entity
        ));
        $dispatcher->dispatch($event);

        return $this->render('@JermBundle/Modal/notification.html.twig', array(
            'message' => 'Subscribers created',
            'type' => 'success',
            'modal_size' => 'modal-sm',
            'fade' => true
        ));
    }
}