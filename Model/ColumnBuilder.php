<?php

namespace Musicjerm\Bundle\JermBundle\Model;

use Symfony\Component\Security\Core\Security;

class ColumnBuilder
{
    /** @var array */
    private $config;

    /** @var Security */
    private $security;

    /** @var array */
    private $colOrder;

    /** @var array */
    private $columns;

    /** @var array */
    private $view;

    /** @var array */
    private $dump;

    /** @var array */
    private $tooltip;

    /** @var string */
    private $key;

    /** @var string */
    private $sortId;

    /** @var string */
    private $sortDir;

    /** @var array */
    private $actionBtns;

    /** @var bool */
    private $actionColRequired = false;

    /** @var integer */
    private $groupBtnCount;

    /**
     * ColumnBuilder constructor.
     * @param array $config
     * @param Security $security
     */
    public function __construct(array $config, Security $security)
    {
        $this->config = $config;
        $this->security = $security;
    }

    /**
     * @throws \Exception
     */
    private function setColOrder()
    {
        if (!isset($this->config['columns'])){
            throw new \Exception('Error: Columns not defined in config');
        }

        if (isset($this->config['colOrder']) && count($this->config['colOrder']) == count($this->config['columns'])){
            $this->colOrder = $this->config['colOrder'];
        }else{
            $this->colOrder = array();
            foreach ($this->config['columns'] as $key=>$val){
                $this->colOrder[] = $key;
            }
        }
    }

    private function setView()
    {
        if (isset($this->config['view']) && is_array($this->config['view'])){
            $this->view = $this->config['view'];
        }else{
            $this->view = array();
            foreach ($this->config['columns'] as $key=>$val){
                $this->view[] = $key;
            }
        }
    }

    private function setDump()
    {
        if (isset($this->config['dump']) && is_array($this->config['dump'])){
            $this->dump = $this->config['dump'];
        }else{
            $this->dump = array();
            foreach ($this->config['columns'] as $key=>$val){
                $this->dump[] = $key;
            }
        }
    }

    private function setKey()
    {
        if (isset($this->config['key'])){
            $this->key = $this->config['key'];
        }else{
            $this->key = null;
        }
    }

    private function setSortId()
    {
        if (isset($this->config['sortId'])){
            $key = array_search($this->config['sortId'], $this->colOrder);
            $this->sortId = $key;
        }else{
            $this->sortId = 0;
        }
    }

    private function setSortDir()
    {
        if (isset($this->config['sortDir'])){
            $this->sortDir = $this->config['sortDir'];
        }else{
            $this->sortDir = 'asc';
        }
    }

    private function setColumns()
    {
        $this->columns = array();
        foreach ($this->colOrder as $key){
            $array = $this->config['columns'][$key];
            if (in_array($key, $this->view)){
                $array['visible'] = true;
            }else{
                $array['visible'] = false;
            }

            if (in_array($key, $this->dump)){
                $array['dumpable'] = true;
            }else{
                $array['dumpable'] = false;
            }
            $this->columns[] = $array;
        }
    }

    private function setActionBtns()
    {
        $this->actionBtns = array();
        if (isset($this->config['actions']['item'])){
            foreach ($this->config['actions']['item'] as $key=>$actionItem){
                if ($this->security->isGranted($actionItem['role']) && isset($this->key)){
                    isset($actionItem['method']) ?: $actionItem['method'] = 'data-href';
                    isset($actionItem['path']) ?: $actionItem['path'] = $key;
                    isset($actionItem['params']) ?: $actionItem['params'] = [];
                    if (!isset($actionItem['target']) || $actionItem['target'] === 0){
                        $actionItem['target'] = 0;
                        $actionItem['shift'] = false;
                        $this->actionColRequired = true;
                    }else{
                        foreach ($this->columns as $subKey=>$val){
                            if ($val['data'] === $actionItem['target']){
                                $actionItem['target'] = $subKey;
                                $actionItem['shift'] = true;
                            }
                        }
                    }

                    $this->actionBtns[] = $actionItem;
                }
            }
        }
    }

    private function setTooltip()
    {
        $this->tooltip = array();
        if (isset($this->config['tooltip']) && count($this->config['tooltip']) == count($this->columns)){

            foreach ($this->colOrder as $key=>$val){
                $oldValue = $this->config['tooltip'][$val];
                if ($oldValue > -1){
                    $newValue = array_search($oldValue, $this->colOrder);
                    $this->tooltip[] = $newValue;
                }else{
                    $this->tooltip[] = -1;
                }
            }
        }else{
            for ($num = 0; $this->columns[$num]; $num++){
                $this->tooltip[] = -1;
            }
        }
    }

    private function setGroupBtnCount()
    {
        $this->groupBtnCount = 0;
        if (isset($this->config['actions']['group'])){
            foreach ($this->config['actions']['group'] as $action){
                if ($this->security->isGranted($action['role'])){
                    $this->groupBtnCount++;
                }
            }
        }
    }

    private function shiftColumns()
    {
        if (count($this->actionBtns) > 0){
            foreach ($this->actionBtns as $key=>$actionBtn){
                if ($actionBtn['shift']){
                    $this->actionBtns[$key]['target']++;
                }
            }

            $this->sortId++;
            array_unshift($this->columns, array(
                'data' => 'dtActionCol',
                'orderable' => false,
                'dumpable' => false,
                'visible' => $this->actionColRequired
            ));
            array_unshift($this->tooltip, -1);
            foreach ($this->tooltip as $key=>$tt){
                $tt > -1 ? $this->tooltip[$key]++ : false;
            }
        }

        if ($this->groupBtnCount > 0){
            array_push($this->columns, array(
                'data' => 'dtSelectorCol',
                'orderable' => false,
                'dumpable' => false
            ));
        }
    }

    /**
     * @throws \Exception
     */
    public function buildColumns()
    {
        $this->setColOrder();
        $this->setView();
        $this->setDump();
        $this->setKey();
        $this->setSortId();
        $this->setSortDir();
        $this->setColumns();
        $this->setActionBtns();
        $this->setTooltip();
        $this->setGroupBtnCount();
        $this->shiftColumns();
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getSortId()
    {
        return $this->sortId;
    }

    /**
     * @return string
     */
    public function getSortDir()
    {
        return $this->sortDir;
    }

    /**
     * @return array
     */
    public function getActionBtns()
    {
        return $this->actionBtns;
    }

    /**
     * @return int
     */
    public function getGroupBtnCount()
    {
        return $this->groupBtnCount;
    }

    /**
     * @return array
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @return array
     */
    public function getDump()
    {
        return $this->dump;
    }

    /**
     * @return array
     */
    public function getTooltip()
    {
        return $this->tooltip;
    }

}