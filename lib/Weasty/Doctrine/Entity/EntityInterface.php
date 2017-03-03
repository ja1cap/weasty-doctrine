<?php
namespace Weasty\Doctrine\Entity;

/**
 * Interface EntityInterface
 * @package Weasty\Doctrine\Entity
 */
interface EntityInterface extends \ArrayAccess {

    /**
     * @return integer
     */
    public function getId();

    /**
     * @return mixed
     */
    public function getIdentifier();

    /**
     * @return string
     */
    public function getIdentifierField();

}