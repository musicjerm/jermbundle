<?php

namespace Musicjerm\Bundle\JermBundle\Model;

use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class NavModel
{
    /** @var array $navData */
    private $navData;

    /** @var string $currentRoute */
    private $currentRoute;

    /** @var mixed $currentParams */
    private $currentParams;

    /** @var string $defaultIcon */
    private $defaultIcon = 'fa-circle-o';

    /** @var array $userRoles */
    private $userRoles;

    /** @var array $navOutput */
    private $navOutput = array();

    /** @var AuthorizationChecker $context */
    private $authChecker;

    /**
     * NavModel constructor.
     * @param AuthorizationChecker $authChecker
     */
    public function __construct(AuthorizationChecker $authChecker)
    {
        $this->authChecker = $authChecker;
    }

    /**
     * @param array $navData
     * @return NavModel
     */
    public function setNavData($navData)
    {
        $this->navData = $navData;
        return $this;
    }

    /**
     * @param string $currentRoute
     * @return NavModel
     */
    public function setCurrentRoute($currentRoute)
    {
        $this->currentRoute = $currentRoute;
        return $this;
    }

    /**
     * @param mixed $currentParams
     * @return NavModel
     */
    public function setCurrentParams($currentParams)
    {
        $this->currentParams = $currentParams;
        return $this;
    }

    /**
     * @param string $defaultIcon
     * @return NavModel
     */
    public function setDefaultIcon($defaultIcon)
    {
        $this->defaultIcon = $defaultIcon;
        return $this;
    }

    /**
     * @return array
     */
    public function getNavData()
    {
        return $this->navData;
    }

    /**
     * @return string
     */
    public function getCurrentRoute()
    {
        return $this->currentRoute;
    }

    /**
     * @return mixed
     */
    public function getCurrentParams()
    {
        return $this->currentParams;
    }

    /**
     * @return string
     */
    public function getDefaultIcon()
    {
        return $this->defaultIcon;
    }

    /**
     * @param array $userRoles
     * @return NavModel
     */
    public function setUserRoles($userRoles)
    {
        $this->userRoles = $userRoles;
        return $this;
    }

    /**
     * @return array
     */
    public function getUserRoles()
    {
        return $this->userRoles;
    }

    /** @return NavModel */
    public function buildNav()
    {
        if (is_array($this->navData)){
            // level 1
            foreach ($this->navData as $navKey=>$value){
                if (isset($value['route']) && isset($value['role']) && $this->authChecker->isGranted($value['role'])){
                    $params = isset($value['parameters']) ? $value['parameters'] : null;
                    $this->navOutput[$navKey] = array(
                        'route'=>$value['route'],
                        'parameters'=>$params,
                        'active'=>($this->getCurrentRoute() == $value['route'] && $this->getCurrentParams() == $params),
                        'icon'=>isset($value['icon']) ? $value['icon'] : $this->getDefaultIcon()
                    );
                }elseif(is_array($value)){
                    // level 2
                    foreach ($value as $subKey=>$subVal){
                        if (isset($subVal['route']) && isset($subVal['role']) && $this->authChecker->isGranted($subVal['role'])){
                            $params = isset($subVal['parameters']) ? $subVal['parameters'] : null;
                            $this->navOutput[$navKey][$subKey] = array(
                                'route'=>$subVal['route'],
                                'parameters'=>$params,
                                'active'=>($this->getCurrentRoute() == $subVal['route'] && $this->getCurrentParams() == $params),
                                'icon'=>isset($subVal['icon']) ? $subVal['icon'] : $this->getDefaultIcon()
                            );
                        }elseif(is_array($subVal)){
                            // level 3
                            foreach ($subVal as $subSubKey=>$subSubVal){
                                if (isset($subSubVal['route']) && isset($subSubVal['role']) && $this->authChecker->isGranted($subSubVal['role'])){
                                    $params = isset($subSubVal['parameters']) ? $subSubVal['parameters'] : null;
                                    $this->navOutput[$navKey][$subKey][$subSubKey] = array(
                                        'route'=>$subSubVal['route'],
                                        'parameters'=>$params,
                                        'active'=>($this->getCurrentRoute() == $subSubVal['route'] && $this->getCurrentParams() == $params),
                                        'icon'=>isset($subSubVal['icon']) ? $subSubVal['icon'] : $this->getDefaultIcon()
                                    );
                                    if ($this->getCurrentRoute() == $subSubVal['route'] && $this->getCurrentParams() == $params){
                                        $this->navOutput[$navKey][$subKey]['active'] = true;
                                        $this->navOutput[$navKey]['active'] = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $this;
    }

    /** @return array */
    public function getNavOutput()
    {
        return $this->navOutput;
    }
}