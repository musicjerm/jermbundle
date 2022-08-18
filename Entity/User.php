<?php

namespace Musicjerm\Bundle\JermBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, \Serializable
{
    /** @var int */
    protected $id;

    /** @var string */
    protected $username;

    /** @var string */
    protected $password;

    /** @var string */
    protected $email;

    /** @var array */
    protected $roles;

    public function __construct()
    {
        $this->roles = array();
    }

    public function __toString(): string
    {
        return $this->getUsername();
    }

    public function getUserIdentifier(): string
    {
        return $this->getEmail();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT);
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function eraseCredentials(): void{}

    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password
        ));
    }

    public function __serialize(): array
    {
        return array(
            'id' => $this->id,
            'username' => $this->username,
            'password' => $this->password
        );
    }

    public function unserialize($serialized): void
    {
        [$this->id, $this->username, $this->password] = unserialize($serialized, ['allowed_classes' => [self::class]]);
    }

    public function __unserialize($serialized): void
    {
        $this->id = $serialized['id'];
        $this->username = $serialized['username'];
        $this->password = $serialized['password'];
    }

    public function getSalt(): ?string
    {
        //handled by BCRYPT
        return null;
    }
}