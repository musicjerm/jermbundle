<?php

namespace Musicjerm\Bundle\JermBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class CommitRepository extends EntityRepository
{
    public function standardQuery($orderBy, $orderDir, $firstResult, $maxResults, $filters): Query
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy($orderBy, $orderDir)
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        if ($filters['Search'] !== ''){
            $qb
                ->andWhere('c.commit LIKE :search OR c.notes LIKE :search')
                ->setParameter('search', '%' . $filters['Search'] . '%');
        }

        return $qb->getQuery();
    }

    public function findWhatsNew($maxResults): array
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.date', 'desc')
            ->setMaxResults($maxResults);

        return $qb->getQuery()->getArrayResult();
    }
}