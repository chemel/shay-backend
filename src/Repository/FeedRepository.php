<?php

namespace App\Repository;

use App\Entity\Feed;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for managing Feed entities.
 *
 * @method Feed|null find($id, $lockMode = null, $lockVersion = null)
 * @method Feed|null findOneBy(array $criteria, array $orderBy = null)
 * @method Feed[]    findAll()
 * @method Feed[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedRepository extends ServiceEntityRepository
{
    /**
     * Repository constructor.
     *
     * @param ManagerRegistry $registry The Doctrine registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feed::class);
    }

    /**
     * Adds a feed to the database.
     *
     * @param Feed $entity The feed to add
     * @param bool $flush  Whether to flush the changes immediately
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Feed $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Removes a feed from the database.
     *
     * @param Feed $entity The feed to remove
     * @param bool $flush  Whether to flush the changes immediately
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Feed $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Gets all feeds that need to be fetched.
     *
     * Returns enabled feeds that either:
     * - Have never been fetched (fetchAt is NULL)
     * - Are due for fetching (fetchAt is in the past)
     *
     * Results are ordered by fetchAt in ascending order.
     *
     * @return Feed[] Array of feeds that need to be fetched
     */
    public function getFeedsToFetch()
    {
        $now = new \DateTime('now');

        return $this->createQueryBuilder('f')
            ->andWhere('f.enabled = true')
            ->andWhere('(f.fetchAt IS NULL OR f.fetchAt < :now)')
            ->setParameter('now', $now)
            ->orderBy('f.fetchAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
