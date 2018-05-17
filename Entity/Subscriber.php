<?php

namespace Musicjerm\Bundle\JermBundle\Entity;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="Musicjerm\Bundle\JermBundle\Repository\SubscriberRepository")
 * @UniqueEntity(fields={"entity", "entityId", "user"}, message="User has already subscribed to this item")
 * @Assert\Expression(
 *     "this.getSystem() or this.getEmail()",
 *     message="Must opt to receive at least one type of notification"
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class Subscriber
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
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $entity;

    /**
     * @var string
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $entityId;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user", referencedColumnName="id")
     * @Assert\NotBlank()
     */
    private $user;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $email;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $system;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_created", referencedColumnName="id")
     */
    private $userCreated;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_updated", referencedColumnName="id")
     */
    private $userUpdated;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    private $dateCreated;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    private $dateUpdated;

    public function __toString()
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

    /** @ORM\PrePersist() */
    public function setDateCreated(): self
    {
        $this->dateCreated = new \DateTime();
        return $this;
    }

    public function getDateCreated(): \DateTime
    {
        return $this->dateCreated;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
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