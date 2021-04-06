<?php

namespace Dimkabelkov\CriteriaHelper\Criteria;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\QueryBuilder;
use Dimkabelkov\CriteriaHelper\Exception\InvalidComparisonException;
use Dimkabelkov\CriteriaHelper\Exception\InvalidCriteriaException;

class DoctrineCriteria
{
    /**
     * @var QueryBuilder
     */
    private QueryBuilder $qb;

    public function __construct(QueryBuilder $qb)
    {
        $this->qb = $qb;
    }

    /**
     * @param array $criteria
     * @param Composite $expr
     *
     * @return Composite
     *
     * @throws InvalidCriteriaException
     * @throws InvalidComparisonException
     *
     * @internal param QueryBuilder $qb
     *
     * @example
     * $criteria = array(
     *      'or' => array(
     *          array('field' => 'field1', 'op' => 'like', 'value' => '%field1Value%'),
     *          array('field' => 'field2', 'op' => 'like', 'value' => '%field2Value%')
     *      ),
     *      'and' => array(
     *          array('field' => 'field3', 'op' => 'eq', 'value' => 3),
     *          array('field' => 'field4', 'op' => 'eq', 'value' => 'four')
     *      ),
     *      array('field' => 'field5', 'op' => 'neq', 'value' => 5)
     * );
     *
     * $qb = new QueryBuilder();
     * addCriteria($qb, $qb->expr()->andX(), $criteria);
     * echo $qb->getSQL();
     *
     * // Result:
     * // SELECT *
     * // FROM tableName
     * // WHERE ((field1 LIKE '%field1Value%') OR (field2 LIKE '%field2Value%'))
     * // AND ((field3 = '3') AND (field4 = 'four'))
     * // AND (field5 <> '5')
     */
    public function getCriteriaFromArray(array $criteria, Composite $expr = null)
    {
        if (is_null($expr)) {
            $expr = $this->qb->expr()->andX();
        }

        if (count($criteria)) {
            $this->getExpression($criteria, $expr);
        }

        return $expr;
    }

    /**
     * @param array $criteria
     * @param Composite|null $expr
     *
     * @return Composite|null
     *
     * @throws InvalidComparisonException
     * @throws InvalidCriteriaException
     */
    private function getExpression(array $criteria, Composite $expr = null)
    {
        foreach ($criteria as $expression => $comparison) {
            if (strtolower($expression) === 'or') {
                $subExpr = $this->getExpression($comparison, $this->qb->expr()->orX());
                $expr->add($subExpr);

            } else if (strtolower($expression) === 'and') {
                $subExpr = $this->getExpression($comparison, $this->qb->expr()->andX());
                $expr->add($subExpr);

            } else {
                if (!is_array($comparison)) {
                    throw new InvalidCriteriaException('Invalid criteria schema: `' . $expression .' => '. $comparison . '`');
                }

                if ($this->isComparison($comparison)) {
                    $expr->add($this->getComparison($comparison));
                    continue;
                }

                $this->getExpression($comparison, $expr);
            }
        }

        return $expr;
    }

    /**
     * @param array $comparison
     *
     * @return bool
     *
     * @throws InvalidCriteriaException
     */
    private function isComparison(array $comparison): bool
    {
        if (empty($comparison['field']) || empty($comparison['op']) || !isset($comparison['value'])) {
            return false;
        }

        if (!is_string($comparison['field']) || !is_string($comparison['op'])) {
            throw new InvalidCriteriaException(
                'Type error: passed must be object, example: {"field": "email", "op": "eq", "value": "test@test.ru"},'
                . ' given: ' . json_encode($comparison)
            );
        }

        return true;
    }

    /**
     * @param $comparison
     *
     * @return string
     */
    private function mappedComparison($comparison)
    {
        if ($comparison === 'ilike') {
            return 'like';
        }
        return $comparison;
    }

    /**
     * @param $comparison
     *
     * @return bool
     */
    private function isNotField($comparison)
    {
        if ($comparison === 'isNull') {
            return true;
        } else if ($comparison === 'isNotNull') {
            return true;
        }

        return false;
    }

    /**
     * @param array $comparison
     *
     * @return Comparison
     *
     * @throws InvalidComparisonException
     * @throws InvalidCriteriaException
     */
    private function getComparison(array $comparison)
    {
        if (!$this->isComparison($comparison)) {
            throw new InvalidCriteriaException(
                'Type error: passed must be object, example: {"field": "email", "op": "eq", "value": "test@test.ru"},'
                . ' given: ' . json_encode($comparison)
            );
        }

        if (!method_exists($this->qb->expr(), $this->mappedComparison($comparison['op']))) {
            throw new InvalidComparisonException($comparison['op']);
        }

        $field = $comparison['field'];

        $rootAlias = $this->qb->getRootAliases()[0];

        if (!strpos($field, '.')) {
            $field = $rootAlias . '.' . $field;
        }

        if ($comparison['op'] === 'ilike') {
            $field = 'LOWER(' . $field . ')';
        }

        if ($this->isNotField($comparison['op'])) {
            $comparison = $this->qb
                ->expr()
                ->{$this->mappedComparison($comparison['op'])}(
                    $field
                );

            return $comparison;
        } else if (is_array($comparison['value'])) {
            $literals = [];

            if (!empty($comparison['value'])) {
                foreach ($comparison['value'] as $value) {
                    $literals[] = $this->qb->expr()->literal($value);
                }
            }

            $comparison = $this->qb
                ->expr()
                ->{$this->mappedComparison($comparison['op'])}(
                    $field, $literals
                );

            return $comparison;
        } else {
            $comparison = $this->qb
                ->expr()
                ->{$this->mappedComparison($comparison['op'])}(
                    $field,
                    $this->qb->expr()->literal($comparison['value'])
                );
            return $comparison;
        }

        throw new InvalidComparisonException($comparison['op']);
    }
}
