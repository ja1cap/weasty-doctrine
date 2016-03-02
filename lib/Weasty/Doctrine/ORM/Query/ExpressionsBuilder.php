<?php
namespace Weasty\Doctrine\ORM\Query;

use Doctrine\ORM\Query\Expr;

/**
 * Class ExpressionsBuilder
 * @package Weasty\Bundle\DoctrineBundle\ORM\Query
 */
class ExpressionsBuilder extends Expr
{

    /**
     * @var mixed
     */
    private $select;

    /**
     * @var array
     */
    private $where;

    /**
     * @var array
     */
    private $having;

    /**
     * @var array
     */
    private $order_by;

    /**
     * @var integer
     */
    private $page = 1;

    /**
     * @var integer
     */
    private $perPage = 12;

    /**
     * @var array
     */
    private $group_by;

    public function __construct(){
        $this->where = array();
        $this->having = array();
        $this->order_by = array();
        $this->group_by = array();
    }

    /**
     * @param mixed $select
     */
    public function setSelect($select)
    {
        $this->select = $select;
    }

    /**
     * @return mixed
     */
    public function getSelect()
    {
        return $this->select;
    }

    /**
     * @param $groupBy
     * @return ExpressionsBuilder
     */
    public function addGroupBy($groupBy){
        $this->group_by[] = new Expr\GroupBy(func_get_args());
        return $this;
    }

    public function getGroupBy()
    {
        return $this->group_by;
    }

    public function removeGroupBy(){
        $this->group_by = array();
        return $this;
    }

    /**
     * @param $expression
     * @return ExpressionsBuilder
     */
    public function addHaving($expression){
        $this->having[] = $expression;
        return $this;
    }

    public function getHaving()
    {
        return $this->having;
    }

    /**
     * @param $sort
     * @param null $order
     * @return ExpressionsBuilder
     */
    public function addOrderBy($sort, $order = null){
        $this->order_by[] = new Expr\OrderBy($sort, $order);
        return $this;
    }

    /**
     * @return array
     */
    public function getOrderBy()
    {
        return $this->order_by;
    }

    /**
     * @return ExpressionsBuilder
     */
    public function removeOrderBy()
    {
        $this->order_by = array();
        return $this;
    }

    /**
     * @param $expression
     * @return ExpressionsBuilder
     */
    public function addWhere($expression){
        $this->where[] = $expression;
        return $this;
    }

    /**
     * @return array
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * @param $page
     * @return ExpressionsBuilder
     */
    public function setPage($page)
    {
        $this->page = (int)$page;
        return $this;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param int $perPage
     * @return ExpressionsBuilder
     */
    public function setPerPage($perPage)
    {
        $this->perPage = (int)$perPage;
        return $this;
    }

    /**
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * @param $query
     * @param $search_field
     * @return $this
     */
    public function setSearchQuery($query, $search_field){

        $query = trim((string)$query);
        $queryParts = array_filter(explode(' ', $query));

        $casesExpr = array();

        if($queryParts){

            foreach($queryParts as $id => $queryPart){
                $queryPart = strtolower($queryPart);
                $casesExpr[$id] = $this->like($this->lower($search_field), "'%$queryPart%'");
            }
        }

        $this->addWhere(call_user_func_array(array($this, 'orX'), $casesExpr));

        return $this;

    }

    /**
     * @return string
     */
    function __toString()
    {
        return md5(
            serialize($this->getSelect())
            . serialize($this->getWhere())
            . serialize($this->getHaving())
            . serialize($this->getOrderBy())
            . serialize($this->getPage())
            . serialize($this->getPerPage())
        );
    }

}
