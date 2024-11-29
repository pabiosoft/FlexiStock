<?php

namespace App\Repository;

use App\Entity\EmailVerification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EmailVerificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailVerification::class);
    }

    public function findByToken(string $token): ?EmailVerification
    {
        return $this->findOneBy(['token' => $token]);
    }

    public function removeExpired(): void
    {
        $qb = $this->createQueryBuilder('ev')
            ->delete()
            ->where('ev.expiresAt < :now')
            ->setParameter('now', new \DateTime());

        $qb->getQuery()->execute();
    }
}
