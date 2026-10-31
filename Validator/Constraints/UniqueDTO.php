<?php

namespace Musicjerm\Bundle\JermBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Usable as an attribute or, while doctrine/annotations is installed, a docblock annotation:
 * #[UniqueDTO(entityClass: Invite::class, fields: ['email'], errorPath: 'email')]
 *
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class UniqueDTO extends Constraint
{
    public $message = 'This value is already used.';
    public $errorPath;
    public $entityClass;
    public $ignoreNull = true;
    public $fields = array();

    /**
     * @param array|string|null $entityClass the entity class, or every option as an array (the annotation form)
     */
    public function __construct(
        array|string|null $entityClass = null,
        ?array $fields = null,
        ?string $errorPath = null,
        ?string $message = null,
        ?bool $ignoreNull = null,
        ?array $groups = null,
        mixed $payload = null,
        array $options = [],
    ) {
        if (\is_array($entityClass)) {
            $options = array_merge($entityClass, $options);
        } elseif ($entityClass !== null) {
            $options['entityClass'] = $entityClass;
        }

        foreach (['fields' => $fields, 'errorPath' => $errorPath, 'message' => $message, 'ignoreNull' => $ignoreNull] as $name => $value) {
            if ($value !== null) {
                $options[$name] = $value;
            }
        }

        parent::__construct($options, $groups, $payload);
    }

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