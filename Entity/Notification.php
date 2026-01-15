<?php

namespace Musicjerm\Bundle\JermBundle\Entity;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Musicjerm\Bundle\JermBundle\Repository\NotificationRepository;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user', referencedColumnName: 'id')]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $date = null;

    #[ORM\Column(type: 'string', length: 128)]
    private ?string $subject = null;

    #[ORM\Column(type: 'text')]
    private ?string $message = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $unread = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $class = null;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    private ?string $hyperlink = null;

    public function __toString(): string
    {
        return $this->getSubject();
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

    public function setDate(\DateTime $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function getDateString(): string
    {
        return $this->getDate()->format('Y-m-d @ h:i a');
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setUnread(bool $unread): self
    {
        $this->unread = $unread;
        return $this;
    }

    public function getUnread(): bool
    {
        return $this->unread;
    }

    public function getStatus(): string
    {
        return $this->getUnread() ? 'Unread' : 'Read';
    }

    public function getSubjectStatus(): string
    {
        if ($this->getUnread()){
            return $this->getSubject() . ' (unread)';
        }

        return $this->getSubject();
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setClass(?string $class): self
    {
        $this->class = $class;
        return $this;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setHyperlink(?string $hyperlink): self
    {
        $this->hyperlink = $hyperlink;
        return $this;
    }

    public function getHyperlink(): ?string
    {
        return $this->hyperlink;
    }
}
