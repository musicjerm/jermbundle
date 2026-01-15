<?php

namespace Musicjerm\Bundle\JermBundle\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_CLASS)]
class UniqueDTOedit extends Constraint
{
    public string $message = 'This value is already used.';
    public ?string $errorPath = null;
    public ?string $entityClass = null;
    public bool $ignoreNull = true;
    public array $fields = [];

    public function getRequiredOptions(): array
    {
        return ['entityClass', 'fields'];
    }

    public function validatedBy(): string
    {
        return \get_class($this) . 'Validator';
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
