<?php

namespace Musicjerm\Bundle\JermBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueDTO extends Constraint
{
    public $message = 'This value is already used.';
    public $errorPath;
    public $entityClass;
    public $ignoreNull = true;
    public $fields = array();

    public function getRequiredOptions(): array
    {
        return ['entityClass', 'fields'];
    }

    public function validatedBy(): string
    {
        return \get_class($this) . 'Validator';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}