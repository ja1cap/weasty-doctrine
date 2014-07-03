<?php
namespace Weasty\Doctrine\Mapper;

use Doctrine\Common\Collections\Collection;
use Weasty\Doctrine\Entity\AbstractEntity;

/**
 * Class EntityCollectionMapper
 * @package Weasty\Doctrine\Mapper
 */
class EntityCollectionMapper {

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $collection;

    /**
     * @var callable
     */
    protected $createClosure;

    /**
     * @var callable
     */
    protected $addClosure;

    /**
     * @var callable
     */
    protected $removeClosure;

    /**
     * @param Collection $collection
     * @param callable $createClosure
     * @param callable $addClosure
     * @param callable $removeClosure
     */
    function __construct(Collection $collection, $createClosure, $addClosure, $removeClosure)
    {
        $this->collection = $collection;
        $this->createClosure = $createClosure;
        $this->addClosure = $addClosure;
        $this->removeClosure = $removeClosure;
    }

    /**
     * @param array $data
     * @param string|int $identifier
     * @return Collection
     */
    public function map(array $data = array(), $identifier = 'id'){


        /**
         * @var $existingEntities AbstractEntity[]
         */
        $existingEntities = $this->getItems()->toArray();
        $existingEntitiesIndexedByIdentifier = array();

        foreach($existingEntities as $existingEntity){
            $existingEntitiesIndexedByIdentifier[$existingEntity[$identifier]] = $existingEntity;
        }

        $entityIdentifiers = array();

        foreach($data as $entityData){

            if(isset($entityData[$identifier])){
                $itemIdentifier = $entityData[$identifier];
            } else {
                $itemIdentifier = null;
            }

            if(is_array($entityData)){

                $entity = null;

                if($itemIdentifier && isset($existingEntitiesIndexedByIdentifier[$itemIdentifier])){

                    $entity = $existingEntitiesIndexedByIdentifier[$itemIdentifier];

                    if($entity instanceof AbstractEntity){
                        $entityIdentifiers[] = $itemIdentifier;
                    }

                } else {

                    $entity = $this->createItem();
                    $this->addItem($entity);

                }

                if($entity instanceof AbstractEntity){
                    foreach($entityData as $key => $value){
                        $entity->offsetSet($key, $value);
                    }
                }

            } else if($entityData instanceof AbstractEntity){

                if($itemIdentifier && isset($existingEntitiesIndexedByIdentifier[$itemIdentifier])){

                    $entityIdentifiers[] = $itemIdentifier;

                } else {

                    $this->addItem($entityData);

                }

                continue;

            }

        }

        foreach($existingEntities as $existingEntity){

            if(!in_array($existingEntity[$identifier], $entityIdentifiers)){
                $this->removeItem($existingEntity);
            }

        }

        return $this->getItems();

    }

    /**
     * @return AbstractEntity
     */
    public function createItem(){
        return call_user_func($this->createClosure);
    }

    /**
     * @param AbstractEntity $entity
     * @return mixed
     */
    public function addItem(AbstractEntity $entity){
        return call_user_func($this->addClosure, $entity);
    }

    /**
     * @param AbstractEntity $entity
     * @return mixed
     */
    public function removeItem(AbstractEntity $entity){
        return call_user_func($this->removeClosure, $entity);
    }

    /**
     * @return Collection
     */
    public function getItems(){
        return $this->collection;
    }

} 