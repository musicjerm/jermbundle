<?php

namespace Musicjerm\Bundle\JermBundle\Form;

use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * BatchSubscriberModel
 *
 * @Assert\Expression(
 *     "this.getSystem() or this.getEmail()",
 *     message="Must select at least one type of notification"
 * )
 */
class BatchSubscriberModel
{
    /**
     * @var array $id
     */
    private $id;

    /**
     * @var User[] $users
     * @Assert\NotBlank()
     */
    private $users;

    /**
     * @var bool $email
     */
    private $email;

    /**
     * @var bool $system
     */
    private $system;

    /**
     * Set id
     * @param array $id
     * @return BatchSubscriberModel
     */
    public function setId($id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get id
     * @return array
     */
    public function getId(): array
    {
        return $this->id;
    }

    /**
     * Set users
     * @param User[] $users
     * @return BatchSubscriberModel
     */
    public function setUsers($users): self
    {
        $this->users = $users;
        return $this;
    }

    /**
     * Get users
     * @return User[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    /**
     * Set email
     * @param bool $email
     * @return BatchSubscriberModel
     */
    public function setEmail($email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get email
     * @return bool
     */
    public function getEmail(): bool
    {
        return $this->email;
    }

    /**
     * Set system
     * @param bool $system
     * @return BatchSubscriberModel
     */
    public function setSystem($system): self
    {
        $this->system = $system;
        return $this;
    }

    /**
     * Get system
     * @return bool
     */
    public function getSystem(): bool
    {
        return $this->system;
    }
}