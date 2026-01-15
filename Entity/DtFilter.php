<?php

namespace Musicjerm\Bundle\JermBundle\Entity;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[UniqueEntity(fields: ['user', 'name', 'entity'], message: 'You already have a preset with this name.', errorPath: 'name')]
class DtFilter
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user', referencedColumnName: 'id')]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 128)]
    private ?string $entity = null;

    #[ORM\Column(type: 'string', length: 128)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(type: 'text')]
    private ?string $data = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $isPrimary = null;

    public function __toString(): string
    {
        return $this->name ?: 'Default';
    }

    public function getId(): int
    {
        return $this->id;
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

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setData(string $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setIsPrimary(bool $isPrimary): self
    {
        $this->isPrimary = $isPrimary;
        return $this;
    }

    public function getIsPrimary(): ?bool
    {
        return $this->isPrimary;
    }
}
