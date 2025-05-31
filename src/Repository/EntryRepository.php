<?php

namespace App\Repository;

use App\Entity\Entry;
use App\Entity\Feed;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for managing Entry entities.
 *
 * @method Entry|null find($id, $lockMode = null, $lockVersion = null)
 * @method Entry|null findOneBy(array $criteria, array $orderBy = null)
 * @method Entry[]    findAll()
 * @method Entry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EntryRepository extends ServiceEntityRepository
{
    /**
     * Repository constructor.
     *
     * @param ManagerRegistry $registry The Doctrine registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entry::class);
    }

    /**
     * Adds an entry to the database.
     *
     * @param Entry $entity The entry to add
     * @param bool  $flush  Whether to flush the changes immediately
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Entry $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Removes an entry from the database.
     *
     * @param Entry $entity The entry to remove
     * @param bool  $flush  Whether to flush the changes immediately
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Entry $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Checks if an entry with the given hash exists for a specific feed.
     *
     * @param Feed  $feed The feed to check
     * @param mixed $hash The hash to check
     *
     * @return bool True if the entry exists, false otherwise
     */
    public function exists(Feed $feed, $hash): bool
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.feed = :feedId AND e.hash = :hash')
            ->setParameter('feedId', $feed->getId())
            ->setParameter('hash', $hash)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * Purges old entries from a feed based on its purge date.
     *
     * @param Feed $feed The feed to purge entries from
     *
     * @return bool True if the purge was successful
     */
    public function purge(Feed $feed): bool
    {
        return $this->createQueryBuilder('e')
            ->delete()
            ->where('e.date < :date')
            ->setParameter('date', $feed->getPurgeAfterDaysDate())
            ->getQuery()
            ->getResult()
        ;
    }
}
