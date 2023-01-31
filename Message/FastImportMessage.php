<?php

namespace Musicjerm\Bundle\JermBundle\Message;

class FastImportMessage
{
    private int $userId;
    private string $entityClass;
    private array $importConfig;
    private string $filePath;
    private array $structure;
    private string $pageName;
    private array $queryKeys;
    private bool $updateOnly;

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function setEntityClass(string $entityClass): self
    {
        $this->entityClass = $entityClass;
        return $this;
    }

    public function getImportConfig(): array
    {
        return $this->importConfig;
    }

    public function setImportConfig(array $importConfig): self
    {
        $this->importConfig = $importConfig;
        return $this;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): self
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getStructure(): array
    {
        return $this->structure;
    }

    public function setStructure(array $structure): self
    {
        $this->structure = $structure;
        return $this;
    }

    public function getPageName(): string
    {
        return $this->pageName;
    }

    public function setPageName(string $pageName): self
    {
        $this->pageName = $pageName;
        return $this;
    }

    public function getQueryKeys(): array
    {
        return $this->queryKeys;
    }

    public function setQueryKeys(array $queryKeys): self
    {
        $this->queryKeys = $queryKeys;
        return $this;
    }

    public function isUpdateOnly(): bool
    {
        return $this->updateOnly;
    }

    public function setUpdateOnly(bool $updateOnly): self
    {
        $this->updateOnly = $updateOnly;
        return $this;
    }
}