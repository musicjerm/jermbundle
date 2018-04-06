<?php

namespace Musicjerm\Bundle\JermBundle\Entity;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DtConfig
 *
 * @ORM\Table(name="dt_config")
 * @ORM\Entity()
 * @UniqueEntity(
 *     fields={"user", "name", "entity"}, message="You already have a preset with this name.",
 *     errorPath="name"
 * )
 */
class DtConfig
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=128)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user", referencedColumnName="id")
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="entity", type="string", length=128)
     */
    private $entity;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort_id", type="integer")
     */
    private $sortId;

    /**
     * @var string
     *
     * @ORM\Column(name="sort_dir", type="string", length=128)
     */
    private $sortDir;

    /**
     * @var array
     *
     * @ORM\Column(name="view", type="simple_array")
     * @Assert\NotBlank(message="Please select at least (1) column for view.")
     */
    private $view;

    /**
     * @var array
     *
     * @ORM\Column(name="data_dump", type="simple_array")
     * @Assert\NotBlank(message="Please select at least (1) column for csv dump.")
     */
    private $dataDump;

    /**
     * @var array
     *
     * @ORM\Column(name="tooltip", type="simple_array")
     */
    private $tooltip;

    /**
     * @var array
     *
     * @ORM\Column(name="col_order", type="simple_array")
     */
    private $colOrder;

    /**
     * @var boolean
     * @ORM\Column(name="is_primary", type="boolean")
     */
    private $isPrimary;

    public function __toString()
    {
        return $this->name ?: 'Default';
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     * @param string $name
     * @return DtConfig
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set user
     *
     * @param User $user
     *
     * @return DtConfig
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set entity
     *
     * @param string $entity
     *
     * @return DtConfig
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entity
     *
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set sortId
     * @param int $sortId
     * @return DtConfig
     */
    public function setSortId($sortId)
    {
        $this->sortId = $sortId;
        return $this;
    }

    /**
     * Get sortId
     * @return int
     */
    public function getSortId()
    {
        return $this->sortId;
    }

    /**
     * Set sortDir
     *
     * @param string $sortDir
     *
     * @return DtConfig
     */
    public function setSortDir($sortDir)
    {
        $this->sortDir = $sortDir;

        return $this;
    }

    /**
     * Get sortDir
     *
     * @return string
     */
    public function getSortDir()
    {
        return $this->sortDir;
    }

    /**
     * Set view
     *
     * @param array $view
     *
     * @return DtConfig
     */
    public function setView($view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Get view
     *
     * @return array
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Set dataDump
     *
     * @param array $dataDump
     *
     * @return DtConfig
     */
    public function setDataDump($dataDump)
    {
        $this->dataDump = $dataDump;

        return $this;
    }

    /**
     * Get dataDump
     *
     * @return array
     */
    public function getDataDump()
    {
        return $this->dataDump;
    }

    /**
     * Set tooltip
     *
     * @param array $tooltip
     *
     * @return DtConfig
     */
    public function setTooltip($tooltip)
    {
        $this->tooltip = $tooltip;

        return $this;
    }

    /**
     * Get tooltip
     *
     * @return array
     */
    public function getTooltip()
    {
        return $this->tooltip;
    }

    /**
     * Set tooltip
     *
     * @param array $colOrder
     *
     * @return DtConfig
     */
    public function setColOrder($colOrder)
    {
        $this->colOrder = $colOrder;

        return $this;
    }

    /**
     * Get colOrder
     *
     * @return array
     */
    public function getColOrder()
    {
        return $this->colOrder;
    }

    /**
     * Set isPrimary
     * @param boolean $isPrimary
     * @return DtConfig
     */
    public function setIsPrimary($isPrimary)
    {
        $this->isPrimary = $isPrimary;
        return $this;
    }

    /**
     * Get isPrimary
     * @return boolean
     */
    public function getIsPrimary()
    {
        return $this->isPrimary;
    }
}