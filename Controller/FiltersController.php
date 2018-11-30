<?php

namespace Musicjerm\Bundle\JermBundle\Controller;

use App\Entity\User;
use Musicjerm\Bundle\JermBundle\Entity\DtFilter;
use Musicjerm\Bundle\JermBundle\Form\DtFilterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

class FiltersController extends AbstractController
{
    /**
     * @param Request $request
     * @param UserInterface|User $user
     * @param string $entity
     * @return Response
     */
    public function createAction(Request $request, UserInterface $user, $entity)
    {
        $filtersString = $request->getContent();
        $dtFilter = new DtFilter();
        $dtFilter
            ->setData($filtersString)
            ->setEntity($entity)
            ->setUser($user);

        $filterPresetForm = $this->createForm(DtFilterType::class, $dtFilter, array(
            'action' => $this->generateUrl('jerm_bundle_data_filters_create', ['entity' => $entity])
        ));

        $filterPresetForm->handleRequest($request);

        if (!$filterPresetForm->isSubmitted() || !$filterPresetForm->isValid()){
            return $this->render('@JermBundle/Modal/form.html.twig', array(
                'form' => $filterPresetForm->createView(),
                'header' => 'Save Current Filters as Preset'
            ));
        }

        // clean up string and persist data
        parse_str($dtFilter->getData(), $dataArray);
        $dataArray = reset($dataArray);
        unset($dataArray['_token']);
        $dtFilter->setData(http_build_query($dataArray));

        $em = $this->getDoctrine()->getManager();
        $em->persist($dtFilter);

        if ($dtFilter->getIsPrimary()){
            $dtFilterRepo = $em->getRepository('JermBundle:DtFilter');
            /** @var DtFilter $filter */
            foreach ($dtFilterRepo->findBy(['user' => $user, 'entity' => $entity, 'isPrimary' => true]) as $filter){
                $filter->setIsPrimary(false);
            }
        }
        $em->flush();

        return $this->render('@JermBundle/Modal/notification.html.twig', array(
            'message' => 'Saved!',
            'type' => 'success',
            'full_refresh' => true,
            'fade' => true,
            'modal_size' => 'modal-sm'
        ));
    }

    /**
     * @param Request $request
     * @param UserInterface|User $user
     * @param string $entity
     * @param integer $id
     * @return Response
     */
    public function updateAction(Request $request, UserInterface $user, $entity, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $dtFilterRepo = $em->getRepository('JermBundle:DtFilter');

        /** @var DtFilter $dtFilter */
        $dtFilter = $dtFilterRepo->find($id);

        if (!$dtFilter){
            throw new NotFoundHttpException('Could not find preset.');
        }

        if ($dtFilter->getUser() !== $user || $dtFilter->getEntity() !== $entity){
            throw new AccessDeniedException();
        }

        $dtFilterForm = $this->createForm(DtFilterType::class, $dtFilter, array(
            'action' => $this->generateUrl('jerm_bundle_data_filter_preset_update', ['entity' => $entity, 'id' => $id])
        ));

        $dtFilterForm->handleRequest($request);

        if (!$dtFilterForm->isSubmitted() || !$dtFilterForm->isValid()){
            return $this->render('@JermBundle/Modal/form.html.twig', array(
                'form' => $dtFilterForm->createView(),
                'header' => 'Update '.ucfirst($entity).' Preset'
            ));
        }

        if ($dtFilter->getIsPrimary()){
            $dtFilterRepo = $em->getRepository('JermBundle:DtFilter');
            /** @var DtFilter $filter */
            foreach ($dtFilterRepo->findBy(['user' => $user, 'entity' => $entity, 'isPrimary' => true]) as $filter){
                $filter->setIsPrimary(false);
            }
            $dtFilter->setIsPrimary(true);
        }
        $em->flush();

        return $this->render('@JermBundle/Modal/notification.html.twig', array(
            'message' => 'Preset updated successfully!',
            'type' => 'success',
            'fade' => true,
            'modal_size' => 'modal-sm'
        ));
    }

    /**
     * @param UserInterface|User $user
     * @param integer $id
     * @return Response
     */
    public function deleteAction(UserInterface $user, $id)
    {
        $em = $this->getDoctrine()->getManager();
        /**
         * @var DtFilter $dtFilter
         */
        $dtFilterRepo = $em->getRepository('JermBundle:DtFilter');
        $dtFilter = $dtFilterRepo->find($id);

        if (!$dtFilter || !$dtFilter->getEntity()){
            throw new NotFoundHttpException('The preset could not be found.');
        }

        if ($dtFilter->getUser() !== $user){
            throw new AccessDeniedException();
        }

        $em->remove($dtFilter);
        $em->flush();

        return new Response('Success!');
    }
}