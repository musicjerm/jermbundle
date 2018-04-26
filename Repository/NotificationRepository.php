<?php

namespace Musicjerm\Bundle\JermBundle\Repository;

use Doctrine\ORM\EntityRepository;

class NotificationRepository extends EntityRepository
{
    public function standardQuery($orderBy, $orderDir, $firstResult, $maxResults, $filters, $user)
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.user = ?0')
            ->setParameter(0, $user)
            ->orderBy($orderBy, $orderDir)
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        if ($filters['status'] !== null){
            $qb->andWhere('n.unread = ?1')->setParameter(1, $filters['status']);
        }

        return $qb->getQuery();
    }

    public function countUnread($user): array
    {
        $qb = $this->createQueryBuilder('n')
            ->addSelect('COUNT(n.id)')
            ->where('n.user = ?0')
            ->andWhere('n.unread = ?1')
            ->setParameters([$user, true]);

        return $qb->getQuery()->getArrayResult();
    }

    public function getLatest($user)
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.user = ?0')
            ->setParameter(0, $user)
            ->orderBy('n.id', 'desc')
            ->setFirstResult(0)
            ->setMaxResults(10);

        return $qb->getQuery()->getResult();
    }
}
