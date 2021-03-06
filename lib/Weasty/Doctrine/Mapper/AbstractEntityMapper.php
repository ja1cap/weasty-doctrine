<?php
namespace Weasty\Doctrine\Mapper;

use Doctrine\Common\Persistence\ObjectManager;
use Weasty\Doctrine\Entity\EntityInterface;
use Weasty\Resource\Exception\NotDefinedPropertyException;

/**
 * Class AbstractEntityMapper
 * @package Weasty\Bundle\DoctrineBundle\Mapper
 */
abstract class AbstractEntityMapper {

    /**
     * @var ObjectManager|object
     */
    protected $entityManager;

    /**
     * @var \Weasty\Doctrine\Entity\EntityInterface
     */
    protected $entity;

    /**
     * @param \Weasty\Doctrine\Entity\EntityInterface|null $entity
     */
    function __construct(EntityInterface $entity = null)
    {
        $this->setEntity($entity);
    }

    /**
     * @param EntityInterface $entity
     * @return $this
     */
    public function setEntity( $entity ) {
        $this->entity = $entity;
        return $this;
    }

    /**
     * @return \Weasty\Doctrine\Entity\EntityInterface
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager|object $entityManager
     */
    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager|object
     * @throws \Weasty\Resource\Exception\NotDefinedPropertyException
     */
    protected function getEntityManager()
    {
        if(!$this->entityManager){
            throw new NotDefinedPropertyException();
        }
        return $this->entityManager;
    }

    /**
     * @param $name
     * @return mixed
     */
    function __get($name)
    {
        $method = 'get' . str_replace(" ", "", ucwords(strtr($name, "_-", "  ")));
        if(method_exists($this, $method)){
            return $this->$method();
        }
        return $this->getEntity() ? $this->getEntity()->offsetGet($name) : null;
    }

    /**
     * @param $name
     * @param $value
     */
    function __set($name, $value)
    {
        $method = 'set' . str_replace(" ", "", ucwords(strtr($name, "_-", "  ")));
        if(method_exists($this, $method)){
            $this->$method($value);
        }
        if($entity = $this->getEntity()){
            $entity->offsetSet($name, $value);
        }
    }

} 