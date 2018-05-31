<?php

namespace Musicjerm\Bundle\JermBundle\Validator\Constraints;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueDTOValidator extends ConstraintValidator
{
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param object $value
     * @param Constraint|UniqueDTO $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        $fields = (array) $constraint->fields;
        $repo = $this->registry->getRepository($constraint->entityClass);
        $criteria = array();

        foreach ($fields as $fieldName){
            if ($constraint->ignoreNull && $value->$fieldName === null){
                continue;
            }

            $criteria[$fieldName] = $value->$fieldName;
        }

        if (empty($criteria)){
            return;
        }

        $result = $repo->{'findBy'}($criteria);

        $em = $this->registry->getManager();
        $identifier = $em->getClassMetadata($constraint->entityClass)->getIdentifier()[0];
        $idGetter = 'get' . ucfirst($identifier);

        if (\count($result) === 0){
            return;
        }

        if (\count($result) === 1 && $result[0]->$idGetter() === $value->$identifier){
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->atPath($constraint->errorPath)
            ->setParameter('{{ string }}', $value)
            ->addViolation();
    }
}