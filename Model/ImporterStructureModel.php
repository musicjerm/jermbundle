<?php

namespace Musicjerm\Bundle\JermBundle\Model;

class ImporterStructureModel
{
    public $name;
    public $type;
    public $foreignKey;
    public $required;
    public $length;
    public $primary;
    public $unique;
    public $repo;
    public $position;
    public $error;
    public $warning;

    public function getErrorString(): ?string
    {
        return \count($this->error) > 0 ? implode(', ', $this->error) : null;
    }
}