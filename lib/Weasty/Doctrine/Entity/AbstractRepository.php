<?php
namespace Weasty\Doctrine\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;
use Weasty\Doctrine\ORM\Query\ExpressionsBuilder;

/**
 * Class AbstractRepository
 * @package Weasty\Bundle\DoctrineBundle\Entity
 */
abstract class AbstractRepository extends EntityRepository {

    /**
     * @param $sql
     * @param array $params
     * @param int $fetchMode
     * @return array
     */
    protected function fetchAll($sql, array $params = array(), $fetchMode = \PDO::FETCH_ASSOC){

        $connection = $this->getEntityManager()->getConnection();
        $result = $connection->executeQuery($sql, $params)->fetchAll($fetchMode);

        return $result;

    }

    /**
     * @return object
     */
    public function create(){
        $class = $this->getClassName();
        return new $class;
    }

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    public function convertDqlToSql(QueryBuilder $qb){

        $em = $qb->getEntityManager();

        $fromParts = $qb->getDQLPart('from');
        $qb->resetDQLPart('from');

        /**
         * @var $fromPart \Doctrine\ORM\Query\Expr\From
         */
        foreach($fromParts as $fromPart){

            if(strpos($fromPart->getFrom(), ':') !== false){
                $tableName = $em->getClassMetadata($fromPart->getFrom())->getTableName();
            } else {
                $tableName = $fromPart->getFrom();
            }

            $qb->from($tableName, $fromPart->getAlias(), $fromPart->getIndexBy());

        }

        $rootJoinParts = $qb->getDQLPart('join');
        $qb->resetDQLPart('join');

        if($rootJoinParts){

            foreach($rootJoinParts as $rootAlias => $joinParts){

                /**
                 * @var $joinPart \Doctrine\ORM\Query\Expr\Join
                 */
                foreach($joinParts as $joinPart){

                    $join = $joinPart->getJoin();

                    if($join instanceof QueryBuilder){
                        $join = "(" . $this->convertDqlToSql($join) . ")";
                    } else {
                        if(strpos($join, ':') !== false ){
                            $join = $em->getClassMetadata($join)->getTableName();
                        }
                    }

                    $join = new Expr\Join(
                        $joinPart->getJoinType(), $join, $joinPart->getAlias(), Expr\Join::ON, $joinPart->getCondition(), $joinPart->getIndexBy()
                    );

                    /**
                     * @var \Doctrine\ORM\Query\Expr\Base $dqlPart
                     */
                    $dqlPart = array($rootAlias => $join);
                    $qb->add('join', $dqlPart, true);


                }

            }

        }

        return $qb;

    }

    /**
     * @param $className
     * @param $alias
     * @param null $resultAlias
     * @return Query\ResultSetMapping
     */
    protected function createResultSetMappingFromMetadata($className, $alias, $resultAlias = null){

        $rsm = new Query\ResultSetMapping();
        $metaData = $this->getEntityManager()->getClassMetadata($className);

        $rsm->addEntityResult($className, $alias, $resultAlias);

        if($metaData->discriminatorColumn){
            $rsm->addMetaResult($alias, $metaData->discriminatorColumn['name'], $metaData->discriminatorColumn['fieldName'])
                ->setDiscriminatorColumn($alias, $metaData->discriminatorColumn['name']);
        }

        foreach($metaData->fieldMappings as $fieldMapping){
            $rsm->addFieldResult($alias, $fieldMapping['columnName'], $fieldMapping['fieldName']);
        }

        return $rsm;

    }

    /**
     * @return ExpressionsBuilder
     */
    public function createExpr(){
        return new ExpressionsBuilder();
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param \Weasty\Doctrine\ORM\Query\ExpressionsBuilder $expr
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function applyExpressions(QueryBuilder $qb, ExpressionsBuilder $expr = null){

        if(!$expr instanceof ExpressionsBuilder){
            return $qb;
        }

        $select = $expr->getSelect();
        if($select){
            $qb->select($select);
        }

        $whereExpressions = $expr->getWhere();
        if($whereExpressions){
            $whereExpressions[] = $qb->getDQLPart('where');
            $whereExpressions = array_filter($whereExpressions);
            $qb->add('where', call_user_func_array(array($expr, 'andX'), $whereExpressions));
        }

        $havingExpressions = $expr->getHaving();
        if($havingExpressions){
            $havingExpressions[] = $qb->getDQLPart('having');
            $havingExpressions = array_filter($havingExpressions);
            $qb->add('having', call_user_func_array(array($expr, 'andX'), $havingExpressions));
        }

        $groupExpressions = $expr->getGroupBy();
        foreach($groupExpressions as $id => $groupExpression){
            $qb->add('groupBy', $groupExpression, $id > 0);
        }

        $orderExpressions = $expr->getOrderBy();
        foreach($orderExpressions as $id => $orderExpression){
            $qb->add('orderBy', $orderExpression, $id > 0);
        }

        return $qb;

    }

    /**
     * @param ExpressionsBuilder $expr
     * @param int $fetchMode
     * @return array
     */
    public function getByExpr(ExpressionsBuilder $expr = null, $fetchMode = \PDO::FETCH_ASSOC){


        if($expr == null){
            $expr = $this->createExpr();
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select($expr->getSelect() ?: 'e')
            ->from($this->getClassMetadata()->getTableName(), 'e');


        $this->applyExpressions($qb, $expr);

        return $this->fetchAll((string)$qb, array(), $fetchMode);

    }

    /**
     * @param \Weasty\Doctrine\ORM\Query\ExpressionsBuilder $expr
     * @return int
     */
    public function getAmount(ExpressionsBuilder $expr = null){

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(item.id)')
            ->from(static::getClassName(), 'item');

        if($expr){
            $this->applyExpressions($qb, $expr);
        }

        $query = $qb->getQuery();

        $amount = (int)$query->getSingleScalarResult();

        return $amount;

    }

} 