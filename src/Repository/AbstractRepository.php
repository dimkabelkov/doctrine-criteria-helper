<?php

namespace Dimkabelkov\CriteriaHelper\Repository;

use Dimkabelkov\CriteriaHelper\Query\QueryResult;
use Dimkabelkov\CriteriaHelper\Criteria\DoctrineCriteria;
use Dimkabelkov\CriteriaHelper\Exception\InvalidComparisonException;
use Dimkabelkov\CriteriaHelper\Exception\InvalidCriteriaException;
use Dimkabelkov\CriteriaHelper\Exception\OrderDirectionException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

abstract class AbstractRepository extends ServiceEntityRepository
{
    public const ENTITY_CLASS = '';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, static::ENTITY_CLASS);
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('q');
    }

    /**
     * @param array $criteria
     * @param array $order
     * @param int $skip
     * @param int $limit
     *
     * @return QueryResult
     *
     * @throws OrderDirectionException
     * @throws InvalidComparisonException
     * @throws InvalidCriteriaException
     */
    public function getResultByCriteria(array $criteria, array $order = [], int $skip = 0, int $limit = 25): QueryResult
    {
        $builder = $this->getQueryBuilder();

        if ($criteria) {
            $converter = new DoctrineCriteria($builder);
            $expr = $converter->getCriteriaFromArray($criteria);
            $builder->andWhere($expr);
        }

        foreach ($order as $field => $direction) {
            if (!in_array($direction, ['asc', 'desc'])) {
                throw new OrderDirectionException($direction);
            }

            if (strpos($field, '.') !== false) {
                $builder->addOrderBy($field, $direction);
            } else {
                $rootAlias = $builder->getRootAliases()[0];
                $builder->addOrderBy($rootAlias . '.' . $field, $direction);
            }
        }

        $builder
            ->setFirstResult($skip)
            ->setMaxResults($limit);

        $paginator = new Paginator($builder);
        $items = $paginator->getQuery()->getResult();

        return new QueryResult($items, $paginator->count(), $skip, $limit);
    }

    /**
     * @param array $criteria
     *
     * @return QueryResult
     *
     * @throws InvalidComparisonException
     * @throws InvalidCriteriaException
     * @throws NonUniqueResultException
     */
    public function getOneByCriteria(array $criteria)
    {
        $builder = $this->getQueryBuilder();

        if ($criteria) {
            $converter = new DoctrineCriteria($builder);
            $expr = $converter->getCriteriaFromArray($criteria);
            $builder->andWhere($expr);
        }

        $builder
            ->setMaxResults(1);

        return $builder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array $criteria
     *
     * @return int|mixed|string
     *
     * @throws InvalidComparisonException
     * @throws InvalidCriteriaException
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getCountByCriteria(array $criteria)
    {
        $builder = $this->getQueryBuilder()
            ->select('count(q.id)');

        if ($criteria) {
            $converter = new DoctrineCriteria($builder);
            $expr = $converter->getCriteriaFromArray($criteria);

            $builder->andWhere($expr);
        }

        return $builder->getQuery()->getSingleScalarResult();
    }
}
