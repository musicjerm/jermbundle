<?php

namespace Musicjerm\Bundle\JermBundle\Entity;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Subscriber
 *
 * @ORM\Table(name="subscriber")
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
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="entity", type="string", length=128, nullable=true)
     */
    private $entity;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_id", type="string", length=128, nullable=true)
     */
    private $entityId;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user", referencedColumnName="id")
     * @Assert\NotBlank()
     */
    private $user;

    /**
     * @var bool
     *
     * @ORM\Column(name="email", type="boolean")
     */
    private $email;

    /**
     * @var bool
     *
     * @ORM\Column(name="system", type="boolean")
     */
    private $system;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_created", referencedColumnName="id")
     */
    private $userCreated;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_updated", referencedColumnName="id")
     */
    private $userUpdated;

    /**
     * @var \DateTime
     * @ORM\Column(name="date_created", type="datetime")
     */
    private $dateCreated;

    /**
     * @var \DateTime
     * @ORM\Column(name="date_updated", type="datetime")
     */
    private $dateUpdated;

    public function __toString()
    {
        return $this->getEntity() . ', ID: (' . $this->getEntityId() . ')';
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
     * Set entity
     *
     * @param string $entity
     *
     * @return Subscriber
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
     * Set entityId
     *
     * @param string $entityId
     *
     * @return Subscriber
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * Get entityId
     *
     * @return string
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Subscriber
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set user
     *
     * @param User $user
     *
     * @return Subscriber
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
     * Set email
     *
     * @param boolean $email
     *
     * @return Subscriber
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return bool
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set system
     *
     * @param boolean $system
     *
     * @return Subscriber
     */
    public function setSystem($system)
    {
        $this->system = $system;

        return $this;
    }

    /**
     * Get system
     *
     * @return bool
     */
    public function getSystem()
    {
        return $this->system;
    }

    /**
     * Set userCreated
     * @param User $user
     * @return Subscriber
     */
    public function setUserCreated($user)
    {
        $this->userCreated = $user;
        return $this;
    }

    /**
     * Get userCreated
     * @return User
     */
    public function getUserCreated()
    {
        return $this->userCreated;
    }

    /**
     * Set userUpdated
     * @param User $user
     * @return Subscriber
     */
    public function setUserUpdated($user)
    {
        $this->userUpdated = $user;
        return $this;
    }

    /**
     * Get userUpdated
     * @return User
     */
    public function getUserUpdated()
    {
        return $this->userUpdated;
    }

    /**
     * Set dateCreated
     * @ORM\PrePersist()
     * @return Subscriber
     */
    public function setDateCreated()
    {
        $this->dateCreated = new \DateTime();
        return $this;
    }

    /**
     * Get dateCreated
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set dateUpdated
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     * @return Subscriber
     */
    public function setDateUpdated()
    {
        $this->dateUpdated = new \DateTime();
        return $this;
    }

    /**
     * Get dateUpdated
     * @return \DateTime
     */
    public function getDateUpdated()
    {
        return $this->dateUpdated;
    }

    /**
     * Get dateCreatedString
     * @return string
     */
    public function getDateCreatedString()
    {
        return $this->getDateCreated()->format('Y-m-d');
    }

    /**
     * Get dateUpdatedString
     * @return string
     */
    public function getDateUpdatedString()
    {
        return $this->getDateUpdated()->format('Y-m-d');
    }
}