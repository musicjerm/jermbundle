<?php

namespace Musicjerm\Bundle\JermBundle\Controller;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Musicjerm\Bundle\JermBundle\Form\DtConfigType;
use Musicjerm\Bundle\JermBundle\Model\CSVDataModel;
use Musicjerm\Bundle\JermBundle\Entity\DtConfig;
use Musicjerm\Bundle\JermBundle\Entity\DtFilter;
use Musicjerm\Bundle\JermBundle\Form\BlankFilterType;
use Musicjerm\Bundle\JermBundle\Form\ColumnPresetType;
use Musicjerm\Bundle\JermBundle\Form\FilterPresetType;
use Musicjerm\Bundle\JermBundle\Model\ColumnBuilder;
use Musicjerm\Bundle\JermBundle\Model\ExcelDownloader;
use Musicjerm\Bundle\JermBundle\Model\NavModel;
use Musicjerm\Bundle\JermBundle\Repository\NotificationRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;
use Doctrine\ORM\EntityRepository;

class BaseController extends AbstractController
{
    /**
     * Configuration for DataTables loaded entities
     * Store config files in /src/JBConfig/Entity/
     */
    private $yamlConfig;

    /**
     * Loaded DtConfig
     * @var DtConfig $loadedConfig
     */
    private $loadedConfig;

    /**
     * FilterType for current entity
     * @var string $filterType
     */
    private $filterType;

    /**
     * EntityRepo for current entity
     * @var $entityRepository
     */
    private $entityRepository;

    /** @var User $user */
    private $user;

    /**
     * @param ManagerRegistry $mr
     * @param $configName
     * @param $user
     * @param bool $reset
     * @param int $columnPreset
     * @throws \Exception
     */
    private function setYamlConfig(ManagerRegistry $mr, $configName, $user, $reset = false, $columnPreset = -1)
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
            }elseif (file_exists($dir . "/$configName.yml")){
                $configFile = $dir . "/$configName.yml";
                break;
            }
        }

        if ($configFile === null){
            throw new \Exception('JB Entity config file is missing.', 500);
        }

        $this->yamlConfig = Yaml::parse(file_get_contents($configFile));


        // check user permission
        if (!isset($this->yamlConfig['role']) || !$this->isGranted($this->yamlConfig['role'])){
            throw new AccessDeniedException();
        }

        // check for entity name
        if (!isset($this->yamlConfig['entity'])){
            throw new \Exception("Entity name not set in config.", 500);
        }else{
            $this->entityRepository = $mr->getRepository($this->yamlConfig['entity']);
        }

        // check for page name
        if (!isset($this->yamlConfig['page_name'])){
            throw new \Exception("Page name not set in config.", 500);
        }

        // check for columns
        if (!isset($this->yamlConfig['columns']) || !is_array($this->yamlConfig['columns'])){
            throw new \Exception("Columns are not configured.", 500);
        }

        // check for entity key if item actions are set
        if (!isset($this->yamlConfig['key']) && isset($this->yamlConfig['actions']['item'])){
            throw new \Exception("Key not set for item actions in config.", 500);
        }

        // set filter type
        $normalizer = new CamelCaseToSnakeCaseNameConverter();
        $this->filterType = "App\Form\JBFilter\\".ucfirst($normalizer->denormalize($configName)).'Type';

        // if primary config exists, set as loaded config
        $em = $mr->getManager();
        $dtConfigRepo = $em->getRepository('Musicjerm\Bundle\JermBundle\Entity\DtConfig');

        // scan column presets and remove any with invalid column count
        /** @var DtConfig $config */
        foreach ($dtConfigRepo->findBy(['user' => $user, 'entity' => $configName]) as $config){
            if (count($config->getColOrder()) !== count($this->yamlConfig['columns'])){
                $em->remove($config);
            }
        }
        $em->flush();

        if ($columnPreset >= 0){
            $this->loadedConfig = $dtConfigRepo->find($columnPreset);
        }else {
            $this->loadedConfig = $dtConfigRepo->findOneBy(['user' => $user, 'entity' => $configName, 'isPrimary' => true]);
        }

        if ($this->loadedConfig && !$reset){
            $this->yamlConfig['view'] = $this->loadedConfig->getView();
            $this->yamlConfig['dump'] = $this->loadedConfig->getDataDump();
            $this->yamlConfig['tooltip'] = $this->loadedConfig->getTooltip();
            $this->yamlConfig['colOrder'] = $this->loadedConfig->getColOrder();
            $this->yamlConfig['sortId'] = $this->loadedConfig->getSortId();
            $this->yamlConfig['sortDir'] = $this->loadedConfig->getSortDir();
        }

        // double check for valid params and set defaults if necessary
        if (!isset($this->yamlConfig['view']) || !is_array($this->yamlConfig['view'])){
            $this->yamlConfig['view'] = array_keys($this->yamlConfig['columns']);
        }
        if (!isset($this->yamlConfig['dump']) || !is_array($this->yamlConfig['dump'])){
            $this->yamlConfig['dump'] = array_keys($this->yamlConfig['columns']);
        }
        if (!isset($this->yamlConfig['tooltip']) || count($this->yamlConfig['tooltip']) !== count($this->yamlConfig['columns'])){
            $this->yamlConfig['tooltip'] = array_fill(0, count($this->yamlConfig['columns']), -1);
        }
        if (!isset($this->yamlConfig['colOrder']) || count($this->yamlConfig['colOrder']) !== count($this->yamlConfig['columns'])){
            $this->yamlConfig['colOrder'] = array_keys($this->yamlConfig['columns']);
        }
        if (!isset($this->yamlConfig['sortId']) || !in_array($this->yamlConfig['sortId'], array_keys($this->yamlConfig['columns']))){
            $this->yamlConfig['sortId'] = 0;
        }
        if (!isset($this->yamlConfig['sortDir']) || !in_array($this->yamlConfig['sortDir'], ['asc', 'desc'])){
            $this->yamlConfig['sortDir'] = 'asc';
        }
    }

    private function createFiltersForm(ManagerRegistry $mr, $entity, $user, $columnPreset = -1)
    {
        $this->user = $user;

        $filtersForm = $this->createFormBuilder(['name' => $entity], ['attr' => ['id' => 'standard_data_filters_form']])
            ->setAction($this->generateUrl('jerm_bundle_data_get_csv', ['entity' => $entity, 'column_preset' => $columnPreset]));

        foreach ($this->yamlConfig['filters'] as $filter){
            switch ($filter['type']){
                case 'Text':
                    $filtersForm->add($filter['name'], TextType::class, isset($filter['array']) ? $filter['array'] : []);
                    break;
                case 'Entity':
                    if (isset($filter['restrict_location_and_active']) && $filter['restrict_location_and_active'] === true){
                        $filter['array']['query_builder'] = function(EntityRepository $er){
                            return $er->createQueryBuilder('f')
                                ->where('f.isActive = :isActive')
                                ->andWhere('f.location = :location')
                                ->orderBy('f.id')
                                ->setParameter('isActive', true)
                                ->setParameter('location', $this->user->getLocation());
                        };
                    }
                    if (isset($filter['restrict_is_active']) && $filter['restrict_is_active'] == true){
                        $filter['array']['query_builder'] = function(EntityRepository $er){
                            return $er->createQueryBuilder('f')
                                ->where('f.isActive = ?1')
                                ->orderBy('f.id')
                                ->setParameter(1, 1);
                        };
                    }
                    if (isset($filter['restrict_owner']) && $filter['restrict_owner'] == true){
                        $filter['array']['query_builder'] = function(EntityRepository $er){
                            return $er->createQueryBuilder('f')
                                ->where('f.userCreated = ?1')
                                ->orderBy('f.name')
                                ->setParameter(1, $this->user);
                        };
                    }
                    if (isset($filter['restrict_location']) && $filter['restrict_location'] == true){
                        $filter['array']['query_builder'] = function(EntityRepository $er){
                            return $er->createQueryBuilder('f')
                                ->where('f.location = ?1')
                                ->orderBy('f.id')
                                ->setParameter(1, $this->user->getLocation());
                        };
                    }
                    $filtersForm->add($filter['name'], EntityType::class, $filter['array']);
                    break;
                case 'Choice':
                    if (isset($filter['entity_group']) && $filter['entity_group'] == true){
                        $er = $mr->getRepository($filter['entity_class']);
                        $query = $filter['entity_query'];
                        if (isset($filter['restrict_location']) && $filter['restrict_location'] == true){
                            foreach ($er->$query($this->user->getLocation()) as $val){
                                $filter['array']['choices'][$val[$filter['entity_group']]] = $val[$filter['entity_group']];
                            }
                        }elseif(isset($filter['restrict_owner']) && $filter['restrict_owner'] == true){
                            foreach ($er->$query($this->user) as $val){
                                $filter['array']['choices'][$val[$filter['entity_group']]] = $val[$filter['entity_group']];
                            }
                        }else{
                            foreach ($er->$query() as $val){
                                $filter['array']['choices'][$val[$filter['entity_group']]] = $val[$filter['entity_group']];
                            }
                        }
                    }
                    $filtersForm->add($filter['name'], ChoiceType::class, $filter['array']);
                    break;
                case 'DateRange':
                    $filtersForm->add($filter['name'], TextType::class, array(
                        'label'=> isset($filter['array']['label']) ? $filter['array']['label'] : null
                    ));
                    $filtersForm->add('EndDate', TextType::class);
                    break;
            }
        }

        return $filtersForm->getForm();
    }

    /**
     * @param ManagerRegistry $mr
     * @param UserInterface $user
     * @param $entity
     * @param integer $column_preset
     * @param integer $filter_preset
     * @return Response
     * @throws \Exception
     */
    public function indexAction(ManagerRegistry $mr, UserInterface $user = null, $entity, $column_preset, $filter_preset)
    {
        // redirect to login if user accidentally signed out
        if (\in_array($entity, ["{{ path('login') }}", "{{ path('Login_route') }}"])){
            return $this->redirectToRoute('homepage');
        }

        // configure defaults
        $this->setYamlConfig($mr, $entity, $user, false, $column_preset);

        if (isset($this->yamlConfig['restrict_location']) && $this->yamlConfig['restrict_location'] && $user->getLocation() === null){
            return $this->redirectToRoute('homepage');
        }

        // set column preset selector form
        $columnPresetForm = $this->createForm(ColumnPresetType::class, null, array(
            'user' => $user,
            'entity' => $entity
        ));

        // set active column preset
        if ($this->loadedConfig){
            $columnPresetForm->get('selectLayout')->setData($this->loadedConfig);
            $column_preset = $this->loadedConfig->getId();
        }

        /**
         * set active filter preset
         * @var DtFilter $primaryFilter
         */
        $dtFilterRepo = $mr->getRepository('Musicjerm\Bundle\JermBundle\Entity\DtFilter');
        if ($filter_preset >= 0){
            $primaryFilter = $dtFilterRepo->find($filter_preset);
            if ($primaryFilter && $primaryFilter->getEntity() !== $entity){
                $primaryFilter = null;
            }
        }else{
            $primaryFilter = $dtFilterRepo->findOneBy(['user' => $user, 'entity' => $entity, 'isPrimary' => true]);
        }

        // check for filters - create form
        if (class_exists($this->filterType)){
            $filtersForm = $this->createForm("$this->filterType", null, array(
                'action' => $this->generateUrl('jerm_bundle_data_get_csv', ['entity' => $entity, 'columnPreset' => $column_preset]),
                'attr' => ['id' => 'standard_data_filters_form'],
                'filter_preset' => $primaryFilter
            ));
        }elseif(isset($this->yamlConfig['filters'])){
            $filtersForm = $this->createFiltersForm($mr, $entity, $user, $column_preset);
        }else{
            $filtersForm = $this->createForm(BlankFilterType::class, null, array(
                'action' => $this->generateUrl('jerm_bundle_data_get_csv', ['entity' => $entity, 'columnPreset' => $column_preset]),
                'attr' => ['id' => 'standard_data_filters_form']
            ));
        }

        // load number of rows per page
        if (method_exists($user, 'getSettingRpp')){
            $settingRpp = $user->getSettingRpp();
        }else{
            $settingRpp = 10;
        }

        // set filter preset selector form
        $filterPresetForm = $this->createForm(FilterPresetType::class, null, array(
            'user' => $user,
            'entity' => $entity
        ));
        $filterPresetForm->get('selectPreset')->setData($primaryFilter);

        // define preset data if exists
        $presetData = array();
        if (null !== $primaryFilter){
            $filter_preset = $primaryFilter->getId();
            $dataString = $primaryFilter->getData();
            parse_str($dataString, $presetData);
        }

        // loop form fields, set any defaults
        foreach ($filtersForm->all() as $child){

            // check for preset data
            if (array_key_exists($child->getName(), $presetData)){

                // if field type is entity and data set, query for entity
                if ($child->getConfig()->getType()->getBlockPrefix() === 'entity' && $presetData[$child->getName()]){
                    // get repo and set entity
                    $classRepo = $mr->getRepository($child->getConfig()->getOption('class'));
                    $child->setData($classRepo->find($presetData[$child->getName()]));
                } elseif ($presetData[$child->getName()]) {
                    // set data
                    $child->setData($presetData[$child->getName()]);
                }
            }elseif (isset($this->yamlConfig['filters']) && is_array($this->yamlConfig['filters'])){
                if ($filterKey = array_search($child->getName(), array_column($this->yamlConfig['filters'], 'name'))){
                    $filterChild = $this->yamlConfig['filters'][$filterKey];

                    // set default location if required in config
                    if (isset($filterChild['default_location']) && $filterChild['default_location'] && method_exists($user, 'getLocation')){
                        $child->setData($user?->getLocation());
                    }
                }
            }
        }

        if (isset($this->yamlConfig['template'])){
            $template = $this->yamlConfig['template'];
        }else{
            $template = '@JermBundle/Base/data_index.html.twig';
        }

        return $this->render($template, array(
            'yaml_config' => $this->yamlConfig,
            'entity' => $entity,
            'filters_form' => $filtersForm->createView(),
            'advanced_filters' => isset($this->yamlConfig['advanced_filters']),
            'setting_rpp' => $settingRpp,
            'column_preset_form' => $columnPresetForm->createView(),
            'filter_preset_form' => $filterPresetForm->createView(),
            'active_column_preset' => $column_preset,
            'active_filter_preset' => $filter_preset
        ));
    }

    /**
     * @param ManagerRegistry $mr
     * @param UserInterface $user
     * @param $entity
     * @param int $column_preset
     * @return JsonResponse
     * @throws \Exception
     */
    public function dataColumnsAction(ManagerRegistry $mr, UserInterface $user, $entity, $column_preset = -1)
    {
        $this->setYamlConfig($mr, $entity, $user, false, $column_preset);
        $security = new Security($this->container);

        $columnBuilder = new ColumnBuilder($this->yamlConfig, $security);
        $columnBuilder->buildColumns();

        return new JsonResponse(array(
            'columns' => $columnBuilder->getColumns(),
            'key' => $columnBuilder->getKey(),
            'sort' => $columnBuilder->getSortId(),
            'sortDir' => $columnBuilder->getSortDir(),
            'actionBtns' => $columnBuilder->getActionBtns(),
            'groupBtns' => $columnBuilder->getGroupBtnCount(),
            'tooltip' => $columnBuilder->getTooltip()
        ));
    }

    /**
     * @param ManagerRegistry $mr
     * @param Request $request
     * @param UserInterface $user
     * @param $entity
     * @param int $column_preset
     * @return JsonResponse
     * @throws \Exception
     */
    public function dataQueryAction(ManagerRegistry $mr, Request $request, UserInterface $user, $entity, $column_preset = -1)
    {
        $this->setYamlConfig($mr, $entity, $user, false, $column_preset);
        $security = new Security($this->container);

        $columnBuilder = new ColumnBuilder($this->yamlConfig, $security);
        $columnBuilder->buildColumns();

        // check for filters - create form and get submitted data
        if (class_exists($this->filterType)){
            $filtersForm = $this->createForm("$this->filterType");
            $filtersForm->handleRequest($request);
            if ($filtersForm->isSubmitted() && $filtersForm->isValid()){
                $filterData = $filtersForm->getData();
            }
        }elseif(isset($this->yamlConfig['filters'])){
            $filtersForm = $this->createFiltersForm($mr, $entity, $user);
            $filtersForm->handleRequest($request);
            if ($filtersForm->isSubmitted() && $filtersForm->isValid()){
                $filterData = $filtersForm->getData();
            }
        }

        // set query parameters
        $order = $request->get('order')[0]['column'];
        $orderBy = $columnBuilder->getColumns()[$order]['sort'];
        $orderDir = $request->get('order')[0]['dir'];
        $firstResult = $request->get('start');
        $maxResults = $request->get('length');
        isset($filterData) ?: $filterData = [];

        // set user's length setting
        if (method_exists($user, 'getSettingRpp') && method_exists($user, 'setSettingRpp') && $user->getSettingRpp() !== (int) $maxResults){
            $em = $mr->getManager();
            $user->setSettingRpp((int) $maxResults);
            $em->flush();
        }

        // check for entity query method
        if (array_key_exists('query', $this->yamlConfig) && $this->yamlConfig['query'] !== null){
            $queryMethod = $this->yamlConfig['query'];
        }else{
            $queryMethod = 'standardQuery';
        }
        if (!method_exists($this->entityRepository, $queryMethod)){
            throw new \Exception("$queryMethod has not been implemented in your " . $this->yamlConfig['entity'] . ' repository class.');
        }

        // query entities - paginate
        $query = $this->entityRepository->$queryMethod($orderBy, $orderDir, $firstResult, $maxResults, $filterData, $user);
        $paginatedQuery = new Paginator($query, $fetchJoinCollection = false);

        // loop items and build array for json output
        $entityArray = array();
        foreach ($paginatedQuery as $item){
            $tempArray = array();
            foreach ($columnBuilder->getColumns() as $key=>$col){
                // set action url's or column data
                if ($col['data'] == 'dtActionCol'){
                    $actionBtnArray = array();
                    foreach ($columnBuilder->getActionBtns() as $actionBtn){
                        $keyGetter = 'get'.ucfirst($columnBuilder->getKey());
                        if (method_exists($item, $keyGetter)){
                            $actionBtn['params']['id'] = urlencode($item->$keyGetter());
                            $actionBtn['path'] = $this->generateUrl($actionBtn['path'], $actionBtn['params']);
                            $actionBtnArray[] = $actionBtn;
                        }
                    }
                    $tempArray[$col['data']] = $actionBtnArray;
                }elseif(count($object = explode('.', $col['data'])) > 1 && method_exists($item, $getter = 'get'.ucfirst($object[0]))){

                    unset($objectValue);
                    switch (count($object))
                    {
                        case 4:
                            if ($item->$getter() && method_exists($item->$getter(), $getter1 = 'get'.ucfirst($object[1]))){
                                if ($item->$getter()->$getter1() && method_exists($item->$getter()->$getter1(), $getter2 = 'get'.ucfirst($object[2]))){
                                    if ($item->$getter()->$getter1()->$getter2() && method_exists($item->$getter()->$getter1()->$getter2(), $getter3 = 'get'.ucfirst($object[3]))){
                                        $objectValue = $item->$getter()->$getter1()->$getter2()->$getter3();
                                    }
                                }
                            }
                            $tempArray[$object[0]][$object[1]][$object[2]][$object[3]] = isset($objectValue) ? htmlspecialchars($objectValue?? '') : null;
                            break;
                        case 3:
                            if ($item->$getter() && method_exists($item->$getter(), $getter1 = 'get'.ucfirst($object[1]))){
                                if ($item->$getter()->$getter1() && method_exists($item->$getter()->$getter1(), $getter2 = 'get'.ucfirst($object[2]))){
                                    $objectValue = $item->$getter()->$getter1()->$getter2();
                                }
                            }
                            $tempArray[$object[0]][$object[1]][$object[2]] = isset($objectValue) ? htmlspecialchars($objectValue ?? '') : null;
                            break;
                        case 2:
                            if ($item->$getter() && method_exists($item->$getter(), $getter1 = 'get'.ucfirst($object[1]))){
                                $objectValue = $item->$getter()->$getter1();
                            }
                            $tempArray[$object[0]][$object[1]] = isset($objectValue) ? htmlspecialchars($objectValue ?? '') : null;
                            break;
                    }

                }elseif(method_exists($item, $getter = 'get'.ucfirst($col['data']))){
                    $tempArray[$col['data']] = is_object($item->$getter()) ? htmlspecialchars($item->$getter()->__toString() ?? '') : htmlspecialchars($item->$getter() ?? '');
                }else{
                    $tempArray[$col['data']] = null;
                }
            }
            $entityArray[] = $tempArray;
        }

        // compile all data into array for json output
        $responseData = array(
            'draw' => $request->get('draw'),
            'recordsTotal' => $paginatedQuery->count(),
            'recordsFiltered' => $paginatedQuery->count(),
            'data' => $entityArray
        );

        // return json response
        return new JsonResponse($responseData);
    }

    /**
     * @param ManagerRegistry $mr
     * @param UserInterface $user
     * @param Request $request
     * @param $entity
     * @param int $column_preset
     * @param int $filter_preset
     * @return Response
     * @throws \Exception
     */
    public function getCsvAction(ManagerRegistry $mr, UserInterface $user, Request $request, $entity, $column_preset = -1, $filter_preset = -1)
    {
        $this->setYamlConfig($mr, $entity, $user, false, $column_preset);
        $security = new Security($this->container);

        $columnBuilder = new ColumnBuilder($this->yamlConfig, $security);
        $columnBuilder->buildColumns();

        // check for filters - create form and get submitted data
        if (class_exists($this->filterType)){
            $filtersForm = $this->createForm("$this->filterType");
        }elseif(isset($this->yamlConfig['filters'])){
            $filtersForm = $this->createFiltersForm($mr, $entity, $user);
        }else{
            $filtersForm = $this->createForm(BlankFilterType::class, null, array(
                'action' => $this->generateUrl('jerm_bundle_data_get_csv', ['entity' => $entity, 'columnPreset' => $column_preset])
            ));
        }

        // process form data
        $filtersForm->handleRequest($request);
        if ($filtersForm->isSubmitted() && $filtersForm->isValid()){
            $filterData = $filtersForm->getData();

        }else{
            // check for preset data or set null
            $presetData = array();
            if ($filter_preset >= 0){
                /**
                 * populate filters if requested in api route
                 * @var DtFilter $selectedFilter
                 */
                $dtFilterRepo = $mr->getRepository('Musicjerm\Bundle\JermBundle\Entity\DtFilter');
                $selectedFilter = $dtFilterRepo->find($filter_preset);
                if ($selectedFilter && $selectedFilter->getEntity() === $entity){
                    $dataString = $selectedFilter->getData();
                    parse_str($dataString, $presetData);
                }
            }

            $filterData = array();
            foreach ($filtersForm->all() as $child){
                if (array_key_exists($child->getName(), $presetData)){
                    $filterData[$child->getName()] = $presetData[$child->getName()];
                }else{
                    $filterData[$child->getName()] = null;
                }
            }
        }

        // set query parameters
        $orderBy = $columnBuilder->getColumns()[$columnBuilder->getSortId()]['sort'];
        $orderDir = $columnBuilder->getSortDir();
        $firstResult = null;
        $maxResults = null;
        isset($filterData) ?: $filterData = null;

        // check for entity query method
        if (array_key_exists('query', $this->yamlConfig) && $this->yamlConfig['query'] !== null){
            $queryMethod = $this->yamlConfig['query'];
        }else{
            $queryMethod = 'standardQuery';
        }
        if (!method_exists($this->entityRepository, $queryMethod)){
            throw new \Exception("$queryMethod has not been implemented in your " . $this->yamlConfig['entity'] . ' repository class.');
        }

        /** @var Query $query */
        $query = $this->entityRepository->$queryMethod($orderBy, $orderDir, $firstResult, $maxResults, $filterData, $user);

        // parse data and put "dumpable" columns into array for csv output
        $data = array();
        foreach ($query->getResult() as $item){
            $tempArray = array();
            foreach ($columnBuilder->getColumns() as $col){
                if($col['dumpable'] && count($object = explode('.', $col['data'])) > 1 && method_exists($item, $getter = 'get'.ucfirst($object[0]))){
                    if (count($object) > 2 && $item->$getter() !== null && method_exists($item->$getter(), $getter1 = 'get'.ucfirst($object[1]))){
                        if (count($object) > 3 && method_exists($item->$getter()->$getter1(), $getter2 = 'get'.ucfirst($object[2]))){
                            $getter3 = 'get'.ucfirst($object[3]);
                            $tempArray[] = $item->$getter() && $item->$getter()->$getter1() && $item->$getter()->$getter1()->$getter2() ? $item->$getter()->$getter1()->$getter2()->$getter3() : null;
                        }else{
                            $getter2 = 'get'.ucfirst($object[2]);
                            $tempArray[] = $item->$getter() && $item->$getter()->$getter1() ? $item->$getter()->$getter1()->$getter2() : null;
                        }
                    }else{
                        $getter1 = 'get'.ucfirst($object[1]);
                        $tempArray[] = $item->$getter() ? $item->$getter()->$getter1() : null;
                    }
                }elseif ($col['dumpable'] && method_exists($item, $getter = 'get'.ucfirst($col['data']))){
                    $tempArray[] = $item->$getter();
                }elseif($col['dumpable']){
                    $tempArray[] = null;
                }
            }
            $data[] = $tempArray;
        }

        // set column names
        $columnNames = array();
        foreach ($columnBuilder->getColumns() as $col){
            if (isset($col['dumpable']) && $col['dumpable']){
                $columnNames[] = $col['title'];
            }
        }

        // build csv data
        $dumpModel = new CSVDataModel();
        $dumpModel->setColumnNames($columnNames);
        $dumpModel->setData($data);
        $dataDump = $dumpModel->buildCsv();

        // return to user
        $date = new \DateTime('now');
        $newFileName = $this->getParameter('app_name').'_'.ucfirst($entity).'_Export_'.$date->format('Y-m-d');
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$newFileName.'.csv"');
        $response->setContent($dataDump);
        return $response;
    }

    /**
     * @param Connection $connection
     * @param ManagerRegistry $mr
     * @param UserInterface $user
     * @param Request $request
     * @param string $entity
     * @param int $column_preset
     * @param int $filter_preset
     * @return Response
     * @throws \Exception
     */
    public function getExcelAction(Connection $connection, ManagerRegistry $mr, UserInterface $user, Request $request, string $entity, int $column_preset = -1, int $filter_preset = -1): Response
    {
        // allow some time and start timer
        set_time_limit(0);
        $timeStart = new \DateTime();

        $this->setYamlConfig($mr, $entity, $user, false, $column_preset);
        $security = new Security($this->container);

        $columnBuilder = new ColumnBuilder($this->yamlConfig, $security);
        $columnBuilder->buildColumns();

        // check for filters - create form and get submitted data
        if (class_exists($this->filterType)){
            $filtersForm = $this->createForm("$this->filterType");
        }elseif(isset($this->yamlConfig['filters'])){
            $filtersForm = $this->createFiltersForm($mr, $entity, $user);
        }else{
            $filtersForm = $this->createForm(BlankFilterType::class, null, array(
                'action' => $this->generateUrl('jerm_bundle_data_get_csv', ['entity' => $entity, 'columnPreset' => $column_preset])
            ));
        }

        // process form data
        $filtersForm->handleRequest($request);
        if ($filtersForm->isSubmitted() && $filtersForm->isValid()){
            $filterData = $filtersForm->getData();

        }else{
            // check for preset data or set null
            $presetData = array();
            if ($filter_preset >= 0){
                /**
                 * populate filters if requested in api route
                 * @var DtFilter $selectedFilter
                 */
                $dtFilterRepo = $mr->getRepository('Musicjerm\Bundle\JermBundle\Entity\DtFilter');
                $selectedFilter = $dtFilterRepo->find($filter_preset);
                if ($selectedFilter && $selectedFilter->getEntity() === $entity){
                    $dataString = $selectedFilter->getData();
                    parse_str($dataString, $presetData);
                }
            }

            $filterData = array();
            foreach ($filtersForm->all() as $child){
                if (array_key_exists($child->getName(), $presetData)){
                    $filterData[$child->getName()] = $presetData[$child->getName()];
                }else{
                    $filterData[$child->getName()] = null;
                }
            }
        }

        // set query parameters
        $orderBy = $columnBuilder->getColumns()[$columnBuilder->getSortId()]['sort'];
        $orderDir = $columnBuilder->getSortDir();
        $firstResult = null;
        $maxResults = null;
        isset($filterData) ?: $filterData = null;

        // check for entity query method
        if (array_key_exists('query', $this->yamlConfig) && $this->yamlConfig['query'] !== null){
            $queryMethod = $this->yamlConfig['query'];
        }else{
            $queryMethod = 'standardQuery';
        }
        if (!method_exists($this->entityRepository, $queryMethod)){
            throw new \Exception("$queryMethod has not been implemented in your " . $this->yamlConfig['entity'] . ' repository class.');
        }

        /** @var Query $query */
        $query = $this->entityRepository->$queryMethod($orderBy, $orderDir, $firstResult, $maxResults, $filterData, $user);

        // parse data and put "dumpable" columns into array for csv output
        $data = array();
        foreach ($query->getResult() as $item){
            $tempArray = array();
            foreach ($columnBuilder->getColumns() as $col){
                if($col['dumpable'] && count($object = explode('.', $col['data'])) > 1 && method_exists($item, $getter = 'get'.ucfirst($object[0]))){
                    if (count($object) > 2 && $item->$getter() !== null && method_exists($item->$getter(), $getter1 = 'get'.ucfirst($object[1]))){
                        if (count($object) > 3 && method_exists($item->$getter()->$getter1(), $getter2 = 'get'.ucfirst($object[2]))){
                            $getter3 = 'get'.ucfirst($object[3]);
                            $tempArray[] = $item->$getter() && $item->$getter()->$getter1() && $item->$getter()->$getter1()->$getter2() ? $item->$getter()->$getter1()->$getter2()->$getter3() : null;
                        }else{
                            $getter2 = 'get'.ucfirst($object[2]);
                            $tempArray[] = $item->$getter() && $item->$getter()->$getter1() ? $item->$getter()->$getter1()->$getter2() : null;
                        }
                    }else{
                        $getter1 = 'get'.ucfirst($object[1]);
                        $tempArray[] = $item->$getter() ? $item->$getter()->$getter1() : null;
                    }
                }elseif ($col['dumpable'] && method_exists($item, $getter = 'get'.ucfirst($col['data']))){
                    $tempArray[] = $item->$getter();
                }elseif($col['dumpable']){
                    $tempArray[] = null;
                }
            }
            $data[] = $tempArray;
        }

        // set column names
        $columnNames = array();
        foreach ($columnBuilder->getColumns() as $col){
            if (isset($col['dumpable']) && $col['dumpable']){
                $columnNames[] = $col['title'];
            }
        }

        // merge headers with data
        $allData = array_merge([$columnNames], $data);

        // build excel sheet
        $excelFile = new ExcelDownloader();
        $excelFile->setSheets([0 => substr($this->yamlConfig['page_name'], 0, 31)]);
        $excelFile->setSheetData(0, $allData);
        $lastRow = count($allData);

        // format special text data
        $dataKey = 0;
        foreach ($columnBuilder->getColumns() as $col){
            if (isset($col['dumpable'], $col['format']) && $col['dumpable']){
                $colLetter = $excelFile->getColumnLetter($dataKey + 1);
                for ($rowNum = 2; $rowNum <= $lastRow; $rowNum++)
                {
                    // set explicit value type in the cell
                    $excelFile->setExplicitTextValue(0, "$colLetter$rowNum", $allData[$rowNum - 1][$dataKey], $col['format']);
                }
                // set format for the column
                $excelFile->setSheetRangeFormatText(0, $colLetter . "2:$colLetter" . $lastRow, $col['format']);
            }

            // count key if dumpable
            if (isset($col['dumpable']) && $col['dumpable']){
                $dataKey++;
            }
        }

        $date = new \DateTime('now');
        $newFileName = $this->getParameter('app_name').' '.ucwords(str_replace('_', ' ', $entity)).' Export '.$date->format('Y-m-d His');
        $lastColumn = $excelFile->getColumnLetter(count($columnNames));
        $excelFile->formatSheetHeaders(0, "A1:$lastColumn" . '1');
        $excelFile->setSheetCursor(0);
        $excelFile->setFile($this->getParameter('kernel.project_dir') . '/documents/temp/', $newFileName);
        $excelFile->saveFile();

        $response = new BinaryFileResponse($excelFile->getFile());
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

        $timeEnd = new \DateTime();
        $totalTime = $timeEnd->getTimestamp() - $timeStart->getTimestamp();

        // send to post processor if timeout may occur
        if ($totalTime > 50 && class_exists('App\Controller\PostProcessingSchedulerController')){
            // send to application processor to decide what to do with the file
            $this->forward('App\Controller\PostProcessingSchedulerController::dumpToExcel', array(
                'documentPath' => $this->getParameter('kernel.project_dir') . '/documents/temp/',
                'filename' => $newFileName
            ));
        }

        return $response;
    }

    /**
     * @param ManagerRegistry $mr
     * @param Request $request
     * @param UserInterface|User $user
     * @param $entity
     * @param int $id
     * @return Response
     * @throws \Exception
     */
    public function dataConfigCreateAction(ManagerRegistry $mr, Request $request, UserInterface $user, $entity, $id = -1)
    {
        if ($id >= 0){
            $this->setYamlConfig($mr, $entity, $user, false, $id);
        }else{
            $this->setYamlConfig($mr, $entity, $user, true);
        }

        $em = $mr->getManager();

        $workingConfig = new DtConfig();
        $workingConfig
            ->setUser($user)
            ->setEntity($entity)
            ->setView($this->yamlConfig['view'])
            ->setDataDump($this->yamlConfig['dump'])
            ->setTooltip($this->yamlConfig['tooltip'])
            ->setColOrder($this->yamlConfig['colOrder'])
            ->setSortId($this->yamlConfig['sortId'])
            ->setSortDir($this->yamlConfig['sortDir'])
            ->setIsPrimary(true);

        $columnNames = array();
        foreach ($this->yamlConfig['columns'] as $column)
        {
            $columnNames[] = $column['title'];
        }

        $form = $this->createForm(DtConfigType::class, $workingConfig, array(
            'action' => $this->generateUrl('jerm_bundle_data_column_config_create', ['entity' => $entity]),
            'columns' => $columnNames
        ));

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()){
            $resetUrl = $this->generateUrl('jerm_bundle_data_column_config_create', ['entity' => $entity]);
            return $this->render('@JermBundle/Modal/dt_config_form.html.twig', array(
                'form' => $form->createView(),
                'header' => 'Configure New '.ucwords(str_replace('_', ' ', $entity)).' Preset',
                'colOrder' => $workingConfig->getColOrder(),
                'columnNames' => $columnNames,
                'resetUrl' => $resetUrl
            ));
        }

        $colOrder = array();
        foreach($request->get('dt_config')['colOrder'] as $value){
            $colOrder[] = $value;
        }

        $workingConfig->setColOrder($colOrder);
        $em->persist($workingConfig);

        /**
         * if new config is primary, set old primary to not primary
         * @var DtConfig $dtConfig
         */
        if ($workingConfig->getIsPrimary()){
            $dtConfigRepo = $em->getRepository('Musicjerm\Bundle\JermBundle\Entity\DtConfig');
            foreach ($dtConfigRepo->findBy(['user' => $user, 'entity' => $entity, 'isPrimary' => true]) as $dtConfig){
                $dtConfig->setIsPrimary(false);
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
     * @param ManagerRegistry $mr
     * @param Request $request
     * @param UserInterface $user
     * @param $entity
     * @param $id
     * @return Response
     * @throws \Exception
     */
    public function dataConfigUpdateAction(ManagerRegistry $mr, Request $request, UserInterface $user, $entity, $id)
    {
        $configName = $entity;
        $this->setYamlConfig($mr, $configName, $user);

        $em = $mr->getManager();
        /**
         * @var DtConfig $workingConfig
         */
        $dtConfigRepo = $em->getRepository('Musicjerm\Bundle\JermBundle\Entity\DtConfig');
        $workingConfig = $dtConfigRepo->find($id);

        if (!$workingConfig){
            return $this->render('@JermBundle/Modal/notification.html.twig', array(
                'message' => 'This config does not exist, please refresh your screen.',
                'type' => 'error'
            ));
        }

        $columnNames = array();
        foreach ($this->yamlConfig['columns'] as $column)
        {
            $columnNames[] = $column['title'];
        }

        $form = $this->createForm(DtConfigType::class, $workingConfig, array(
            'action' => $this->generateUrl('jerm_bundle_data_column_config_update', ['entity' => $entity, 'id' => $id]),
            'columns' => $columnNames
        ));

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()){
            $resetUrl = $this->generateUrl('jerm_bundle_data_column_config_update', ['entity' => $entity, 'id' => $id]);
            return $this->render('@JermBundle/Modal/dt_config_form.html.twig', array(
                'form' => $form->createView(),
                'header' => 'Configure '.ucwords(str_replace('_',' ',$entity)).' Preset',
                'colOrder' => $workingConfig->getColOrder(),
                'columnNames' => $columnNames,
                'resetUrl' => $resetUrl
            ));
        }

        $colOrder = array();
        foreach($request->get('dt_config')['colOrder'] as $value){
            $colOrder[] = $value;
        }

        /**
         * Set old primary configs to not primary
         * @var DtConfig $oldPconfig
         */
        if ($form->get('isPrimary')->getData() === true){
            foreach ($dtConfigRepo->findBy(array('user' => $user, 'entity' => $entity, 'isPrimary' => true)) as $oldPconfig){
                $oldPconfig->setIsPrimary(false);
            }
            $workingConfig->setIsPrimary(true);
        }

        $workingConfig->setColOrder($colOrder);
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
     * @param ManagerRegistry $mr
     * @param UserInterface $user
     * @param $id
     * @return Response
     * @throws \Exception
     */
    public function dataConfigDeleteAction(ManagerRegistry $mr, UserInterface $user, $id): Response
    {
        $em = $mr->getManager();
        /**
         * @var DtConfig $dtConfig
         */
        $dtConfigRepo = $em->getRepository('Musicjerm\Bundle\JermBundle\Entity\DtConfig');
        $dtConfig = $dtConfigRepo->find($id);

        if (!$dtConfig || !$dtConfig->getEntity()){
            throw new \Exception('The preset could not be found.', 500);
        }

        if ($dtConfig->getUser() !== $user){
            throw new AccessDeniedException();
        }

        $em->remove($dtConfig);
        $em->flush();

        return new Response('Success!');
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function navAction(Request $request): Response
    {
        $configDir = $this->getParameter('kernel.project_dir') . '/src/JBConfig';

        $fileLocator = new FileLocator([$configDir]);
        $configFile = $fileLocator->locate('nav.yaml');

        $yamlNav = Yaml::parse(file_get_contents($configFile));

        $params = $request->get('current_params');
        if ($request->get('current_route') === 'jerm_bundle_data_index'){
            unset($params['filter_preset'], $params['column_preset']);
        }

        $security = new Security($this->container);
        $navModel = new NavModel($security);
        $navModel
            ->setNavData($yamlNav)
            ->setCurrentRoute($request->get('current_route'))
            ->setCurrentParams($params);

        $navModel->buildNav();

        if (empty($navModel->getNavOutput()) && $this->getParameter('kernel.environment') === 'dev'){
            throw new \Exception('Please configure ' . $configDir . '/Nav.yaml');
        }

        return $this->render('@JermBundle/Base/nav.html.twig', array(
            'nav' => $navModel->getNavOutput()
        ));
    }

    /**
     * @param ManagerRegistry $mr
     * @param UserInterface|null $user
     * @return Response
     */
    public function messageAction(ManagerRegistry $mr, UserInterface $user = null)
    {
        /**
         * @var NotificationRepository $notificationRepo
         */
        $notificationRepo = $mr->getRepository('Musicjerm\Bundle\JermBundle\Entity\Notification');
        $messages = $notificationRepo->getLatest($user);
        $unreadCount = (int) $notificationRepo->countUnread($user)[0][1];

        return $this->render('@JermBundle/Base/messages.html.twig', array(
            'plurality' => $unreadCount === 1 ? 'notification' : 'notifications',
            'unread_count' => $unreadCount,
            'messages' => $messages
        ));
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function clearSessionAction(Request $request)
    {
        $dontClear = array('_security_main');
        $session = $request->getSession();

        foreach ($session->all() as $key=>$val){
            if (!in_array($key, $dontClear) && substr($key, 0, 5) !== '_csrf'){
                $session->remove($key);
            }
        }

        return new Response('Session cleared', 200);
    }
}