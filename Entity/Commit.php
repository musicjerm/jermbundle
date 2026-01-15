<?php

namespace Musicjerm\Bundle\JermBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Musicjerm\Bundle\JermBundle\Repository\CommitRepository;

#[ORM\Table(name: 'git_commit')]
#[ORM\Entity(repositoryClass: CommitRepository::class)]
class Commit
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 128)]
    private ?string $gitRepo = null;

    #[ORM\Column(type: 'string', length: 128)]
    private ?string $commit = null;

    #[ORM\Column(type: 'string', length: 128)]
    private ?string $author = null;

    #[ORM\Column(type: 'text')]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $date = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setGitRepo($gitRepo): self
    {
        $this->gitRepo = $gitRepo;
        return $this;
    }

    public function getGitRepo(): string
    {
        return $this->gitRepo;
    }

    public function setCommit($commit): self
    {
        $this->commit = $commit;
        return $this;
    }

    public function getCommit(): string
    {
        return $this->commit;
    }

    public function getShortCommit(): string
    {
        return substr($this->commit, 0, 7);
    }

    public function setAuthor($author): self
    {
        $this->author = $author;
        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setNotes($notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setDate($date): self
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
        return $this->getDate()->format('Y-m-d h:i a');
    }
}
