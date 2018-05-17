<?php

namespace Musicjerm\Bundle\JermBundle\Entity;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
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
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user", referencedColumnName="id")
     */
    private $user;

    /**
     * @var string
     * @ORM\Column(type="string", length=128)
     */
    private $entity;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $sortId;

    /**
     * @var string
     * @ORM\Column(type="string", length=128)
     */
    private $sortDir;

    /**
     * @var array
     * @ORM\Column(type="simple_array")
     * @Assert\NotBlank(message="Please select at least (1) column for view.")
     */
    private $view;

    /**
     * @var array
     * @ORM\Column(type="simple_array")
     * @Assert\NotBlank(message="Please select at least (1) column for csv dump.")
     */
    private $dataDump;

    /**
     * @var array
     * @ORM\Column(type="simple_array")
     */
    private $tooltip;

    /**
     * @var array
     * @ORM\Column(type="simple_array")
     */
    private $colOrder;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $isPrimary;

    public function __toString(): string
    {
        return $this->name ?: 'Default';
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setEntity(string $entity): self
    {
        $this->entity = $entity;
        return $this;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function setSortId(int $sortId): self
    {
        $this->sortId = $sortId;
        return $this;
    }

    public function getSortId(): int
    {
        return $this->sortId;
    }

    public function setSortDir(string $sortDir): self
    {
        $this->sortDir = $sortDir;
        return $this;
    }

    public function getSortDir(): string
    {
        return $this->sortDir;
    }

    public function setView(array $view): self
    {
        $this->view = $view;
        return $this;
    }

    public function getView(): array
    {
        return $this->view;
    }

    public function setDataDump(array $dataDump): self
    {
        $this->dataDump = $dataDump;
        return $this;
    }

    public function getDataDump(): array
    {
        return $this->dataDump;
    }

    public function setTooltip(array $tooltip): self
    {
        $this->tooltip = $tooltip;
        return $this;
    }

    public function getTooltip(): array
    {
        return $this->tooltip;
    }

    public function setColOrder(array $colOrder): self
    {
        $this->colOrder = $colOrder;
        return $this;
    }

    public function getColOrder(): array
    {
        return $this->colOrder;
    }

    public function setIsPrimary(bool $isPrimary): self
    {
        $this->isPrimary = $isPrimary;
        return $this;
    }

    public function getIsPrimary(): bool
    {
        return $this->isPrimary;
    }
}