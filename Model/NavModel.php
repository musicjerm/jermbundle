<?php

namespace Musicjerm\Bundle\JermBundle\Model;

use Symfony\Component\Security\Core\Security;

class NavModel
{
    /** @var array $navData */
    private array $navData;

    /** @var string $currentRoute */
    private string $currentRoute;

    /** @var mixed $currentParams */
    private mixed $currentParams;

    /** @var string $defaultIcon */
    private string $defaultIcon = 'fa-circle-o';

    /** @var array $navOutput */
    private array $navOutput = array();

    /** @var Security $security */
    private Security $security;

    /**
     * NavModel constructor.
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param array $navData
     * @return NavModel
     */
    public function setNavData(array $navData): self
    {
        $this->navData = $navData;
        return $this;
    }

    /**
     * @param string $currentRoute
     * @return NavModel
     */
    public function setCurrentRoute(string $currentRoute): self
    {
        $this->currentRoute = $currentRoute;
        return $this;
    }

    /**
     * @param mixed $currentParams
     * @return NavModel
     */
    public function setCurrentParams(mixed $currentParams): self
    {
        $this->currentParams = $currentParams;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrentRoute(): string
    {
        return $this->currentRoute;
    }

    /**
     * @return mixed
     */
    public function getCurrentParams(): mixed
    {
        return $this->currentParams;
    }

    /** @return NavModel */
    public function buildNav(): self
    {
            // level 1
            foreach ($this->navData as $navKey=>$value){
                if (isset($value['route'], $value['role']) && $this->security->isGranted($value['role'])){
                    $params = $value['parameters'] ?? [];
                    $this->navOutput[$navKey] = array(
                        'route'=>$value['route'],
                        'parameters'=>$params,
                        'active'=>($this->getCurrentRoute() === $value['route'] && $this->getCurrentParams() === $params),
                        'icon'=> $value['icon'] ?? $this->defaultIcon
                    );
                }elseif(is_array($value)){
                    // level 2
                    foreach ($value as $subKey=>$subVal){
                        if (isset($subVal['route'], $subVal['role']) && $this->security->isGranted($subVal['role'])){
                            $params = $subVal['parameters'] ?? [];
                            $this->navOutput[$navKey][$subKey] = array(
                                'route'=>$subVal['route'],
                                'parameters'=>$params,
                                'active'=>($this->getCurrentRoute() === $subVal['route'] && $this->getCurrentParams() === $params),
                                'icon'=> $subVal['icon'] ?? $this->defaultIcon
                            );
                        }elseif(is_array($subVal)){
                            // level 3
                            foreach ($subVal as $subSubKey=>$subSubVal){
                                if (isset($subSubVal['route'], $subSubVal['role']) && $this->security->isGranted($subSubVal['role'])){
                                    $params = $subSubVal['parameters'] ?? [];
                                    $this->navOutput[$navKey][$subKey][$subSubKey] = array(
                                        'route'=>$subSubVal['route'],
                                        'parameters'=>$params,
                                        'active'=>($this->getCurrentRoute() === $subSubVal['route'] && $this->getCurrentParams() === $params),
                                        'icon'=> $subSubVal['icon'] ?? $this->defaultIcon
                                    );
                                    if ($this->getCurrentRoute() === $subSubVal['route'] && $this->getCurrentParams() === $params){
                                        $this->navOutput[$navKey][$subKey]['active'] = true;
                                        $this->navOutput[$navKey]['active'] = true;
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
    public function getNavOutput(): array
    {
        return $this->navOutput;
    }
}