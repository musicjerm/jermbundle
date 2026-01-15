<?php

namespace Musicjerm\Bundle\JermBundle\Entity;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Musicjerm\Bundle\JermBundle\Repository\SubscriberRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SubscriberRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['entity', 'entityId', 'user'], message: 'User has already subscribed to this item')]
#[Assert\Expression(expression: 'this.getSystem() or this.getEmail()', message: 'Must opt to receive at least one type of notification')]
class Subscriber
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    private ?string $entity = null;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    private ?string $entityId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user', referencedColumnName: 'id')]
    #[Assert\NotBlank]
    private ?User $user = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $email = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $system = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_created', referencedColumnName: 'id')]
    private ?User $userCreated = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_updated', referencedColumnName: 'id')]
    private ?User $userUpdated = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $dateCreated = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $dateUpdated = null;

    public function __toString(): string
    {
        return $this->getEntity() . ', ID: (' . $this->getEntityId() . ')';
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setEntity(?string $entity): self
    {
        $this->entity = $entity;
        return $this;
    }

    public function getEntity(): ?string
    {
        return $this->entity;
    }

    public function setEntityId(?string $entityId): self
    {
        $this->entityId = $entityId;
        return $this;
    }

    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
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

    public function setEmail(bool $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getEmail(): bool
    {
        return $this->email;
    }

    public function setSystem(bool $system): self
    {
        $this->system = $system;
        return $this;
    }

    public function getSystem(): bool
    {
        return $this->system;
    }

    public function setUserCreated(User $user): self
    {
        $this->userCreated = $user;
        return $this;
    }

    public function getUserCreated(): User
    {
        return $this->userCreated;
    }

    public function setUserUpdated(User $user): self
    {
        $this->userUpdated = $user;
        return $this;
    }

    public function getUserUpdated(): User
    {
        return $this->userUpdated;
    }

    #[ORM\PrePersist]
    public function setDateCreated(): self
    {
        $this->dateCreated = new \DateTime();
        return $this;
    }

    public function getDateCreated(): \DateTime
    {
        return $this->dateCreated;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setDateUpdated(): self
    {
        $this->dateUpdated = new \DateTime();
        return $this;
    }

    public function getDateUpdated(): \DateTime
    {
        return $this->dateUpdated;
    }

    public function getDateCreatedString(): string
    {
        return $this->getDateCreated()->format('Y-m-d @ h:i a');
    }

    public function getDateUpdatedString(): string
    {
        return $this->getDateUpdated()->format('Y-m-d @ h:i a');
    }
}
