<?php
namespace Weasty\Doctrine;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\AbstractManagerRegistry;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Class EntitySerializer
 * @package Weasty\Doctrine
 */
class EntitySerializer {

    /**
     * @var \Doctrine\Common\Persistence\AbstractManagerRegistry
     */
    protected $_managerRegistry;

    /**
     * @var int
     */
    protected $_recursionDepth = 0;

    /**
     * @var int
     */
    protected $_maxRecursionDepth = 0;

    public function __construct(AbstractManagerRegistry $_managerRegistry)
    {
        $this->_managerRegistry = $_managerRegistry;
    }

    /**
     * @param $class
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager($class = null)
    {
        return $class ? $this->_managerRegistry->getManagerForClass($class) :$this->_managerRegistry->getManager();
    }

    protected function _serializeEntity($entity)
    {

        if($entity instanceof Proxy){
            $entity->__load();
        }

        $metadata = $this->getEntityMetaData($entity);

        $data = array();

        foreach ($metadata->fieldMappings as $field => $mapping) {

            $reflectionProperty = $this->getReflectionProperty($metadata, $field);
            $value = $reflectionProperty->getValue($entity);
            //$field = strtolower(preg_replace('~(?<=\\w)([A-Z])~', '_$1', $field));

            if ($value instanceof \DateTime) {
                // We cast DateTime to array to keep consistency with array result
                $data[$field] = (array)$value;
            } elseif (is_object($value)) {
                $data[$field] = (string)$value;
            } else {
                $data[$field] = $value;
            }
        }

//        foreach ($metadata->associationMappings as $field => $mapping) {
//
//            $key = Inflector::tableize($field);
//            $reflectionProperty = $this->getReflectionProperty($metadata, $field);
//
//            if ($mapping['isCascadeDetach']) {
//
//                $data[$key] = $reflectionProperty->getValue($entity);
//                if (null !== $data[$key]) {
//                    $data[$key] = $this->_serializeEntity($data[$key]);
//                }
//
//            } elseif ($mapping['isOwningSide'] && $mapping['type'] & ClassMetadata::TO_ONE) {
//
//                if (null !== $reflectionProperty->getValue($entity)) {
//                    if ($this->_recursionDepth < $this->_maxRecursionDepth) {
//                        $this->_recursionDepth++;
//                        $data[$key] = $this->_serializeEntity(
//                            $reflectionProperty
//                                ->getValue($entity)
//                            );
//                        $this->_recursionDepth--;
//                    } else {
//
//                        $data[$key] = $this->getEntityManager()
//                            ->getUnitOfWork()
//                            ->getEntityIdentifier($reflectionProperty->getValue($entity));
//                    }
//
//                } else {
//                    // In some case the relationship may not exist, but we want
//                    // to know about it
//                    $data[$key] = null;
//                }
//            }
//        }

        return $data;
    }

    /**
     * @param $entity
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    private function getEntityMetaData($entity){
        $className = get_class($entity);
        $metadata = $this->getEntityManager($className)->getClassMetadata($className);
        return $metadata;
    }

    /**
     * @param \Doctrine\ORM\Mapping\ClassMetadata $metadata
     * @param $field
     * @return \ReflectionProperty
     */
    private function getReflectionProperty(ClassMetadata $metadata, $field){
        return $metadata->reflFields[$field];
    }

    /**
     * @param $entity
     * @param null $data
     * @return object
     */
    public function populateEntity(&$entity, $data = null){

        if(is_object($entity) && ($data instanceof Collection || is_array($data))){

            foreach($data as $fieldName => $value){

                $this->populateEntityField($entity, $fieldName, $value);

            }

        }

        return $entity;

    }

    /**
     * @param $entity
     * @param $fieldName
     * @param $value
     * @return mixed
     */
    public function populateEntityField(&$entity, $fieldName, $value){

        $methodName = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $fieldName)));

        if(method_exists($entity, $methodName)){
            $entity->$methodName($value);
        }

        return $entity;

    }

    /**
     * Serialize an entity to an array
     *
     * @param $entity
     * @return array
     */
    public function toArray($entity)
    {
        return $this->_serializeEntity($entity);
    }


    /**
     * Convert an entity to a JSON object
     *
     * @param $entity
     * @return string
     */
    public function toJson($entity)
    {
        return json_encode($this->toArray($entity));
    }

    /**
     * Convert an entity to XML representation
     *
     * @param $entity
     * @throws \Exception
     */
    public function toXml($entity)
    {
        throw new \Exception('Not yet implemented for ' . get_class($entity));
    }

    /**
     * Set the maximum recursion depth
     *
     * @param   int     $maxRecursionDepth
     * @return  void
     */
    public function setMaxRecursionDepth($maxRecursionDepth)
    {
        $this->_maxRecursionDepth = $maxRecursionDepth;
    }

    /**
     * Get the maximum recursion depth
     *
     * @return  int
     */
    public function getMaxRecursionDepth()
    {
        return $this->_maxRecursionDepth;
    }

} 
