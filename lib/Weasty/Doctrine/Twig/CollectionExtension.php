<?php
namespace Weasty\Bundle\DoctrineBundle\Twig;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Class CollectionExtension
 * @package Weasty\Bundle\DoctrineBundle\Twig
 */
class CollectionExtension extends \Twig_Extension {

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'weasty_doctrine_collection';
    }

    /**
     * @return array
     */
    public function getFilters(){
        return array(
            new \Twig_SimpleFilter('weasty_doctrine_collection_element_names', array($this, 'getElementNames')),
        );
    }

    /**
     * @param $collection
     * @return null|string
     */
    public function getElementNames($collection){

        if(is_array($collection)){
            $collection = new ArrayCollection($collection);
        }

        if($collection instanceof Collection){

            $names = $collection->map(function($element){

                if(is_string($element)){
                    return $element;
                }

                if(is_array($element)){
                    if(isset($element['name'])){
                        return $element['name'];
                    } else {
                        return current($element);
                    }
                }

                return (string)$element;

            });

            $names = array_filter($names->toArray());
            asort($names);

            return implode(', ', $names);

        } else {

            return null;

        }

    }

} 