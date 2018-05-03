<?php

namespace Musicjerm\Bundle\JermBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Commit
 *
 * @ORM\Table(name="commit")
 * @ORM\Entity(repositoryClass="Musicjerm\Bundle\JermBundle\Repository\CommitRepository")
 */
class Commit
{
    /**
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="git_repo", type="string", length=128)
     */
    private $gitRepo;

    /**
     * @var string
     * @ORM\Column(name="commit", type="string", length=128)
     */
    private $commit;

    /**
     * @var string
     * @ORM\Column(name="author", type="string", length=128)
     */
    private $author;

    /**
     * @var string
     * @ORM\Column(name="notes", type="text")
     */
    private $notes;

    /**
     * @var \DateTime
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function setGitRepo($gitRepo)
    {
        $this->gitRepo = $gitRepo;
        return $this;
    }

    public function getGitRepo(): string
    {
        return $this->gitRepo;
    }

    public function setCommit($commit)
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

    public function setAuthor($author)
    {
        $this->author = $author;
        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setNotes($notes)
    {
        $this->notes = $notes;
        return $this;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setDate($date)
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