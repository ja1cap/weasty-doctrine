<?php
namespace Weasty\Doctrine\Entity;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Weasty\Resource\Routing\RoutableInterface;

/**
 * Class AbstractEntity
 * @package Weasty\Doctrine\Entity
 */
abstract class AbstractEntity implements EntityInterface, RoutableInterface {

    /**
     * @return array
     */
    public function getRouteParameters(){

        return [
            'id' => $this->getId(),
        ];

    }

    /**
     * @return string
     */
    public function getName(){
        if(property_exists($this, 'name')){
            return $this->{'name'};
        }
        elseif(property_exists($this, 'title')){
            return $this->{'title'};
        }
        elseif(method_exists($this, 'getTitle')){
            return $this->getTitle();
        }
        else {
            return (string)$this->getIdentifier();
        }
    }

    /**
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->getId();
    }

    /**
     * @return string
     */
    public function getIdentifierField()
    {
        return 'id';
    }

    /**
     * @deprecated
     * @var array
     */
    private $temps = array();

    /**
     * @deprecated
     * @var array
     */
    protected $files_names = array();

    /**
     * @deprecated
     * @var array
     */
    protected $files = array();

    /**
     * @deprecated
     * @return array
     */
    protected function getFilesNames(){
        return $this->files_names;
    }

    /**
     * @deprecated
     * @return array
     */
    protected function getFiles(){
        return $this->files;
    }

    /**
     * @deprecated
     * @return $this
     */
    public function preUpload()
    {

        $files = $this->getFiles();

        if($files){

            foreach($files as $file_name_field => $file){

                if($file instanceof UploadedFile){

                    $filename = sha1(uniqid(mt_rand(), true));
                    $this->offsetSet($file_name_field, $filename.'.'.$file->getClientOriginalExtension());

                }

            }

        }

        return $this;

    }

    /**
     * @deprecated
     * @return $this
     */
    public function upload()
    {

        $files = $this->getFiles();

        if($files){

            foreach($files as $file_name_field => $file){

                if($file instanceof UploadedFile){

                    $file->move($this->getUploadDirPath(), $this->offsetGet($file_name_field));

                }

            }

        }

        foreach($this->temps as $temp_file_name){

            $temp_file_path = $this->getUploadDirPath().'/'.$temp_file_name;
            if(is_file($temp_file_path)){
                unlink($temp_file_path);
            }

        }

        $this->files = array();
        $this->temps = array();

        return $this;

    }

    /**
     * @deprecated
     * @return $this
     */
    public function removeUpload()
    {
        foreach($this->getFilesNames() as $file_name){
            $file_path = $this->getUploadDirPath() . '/' . $file_name;
            if(is_file($file_path)){
                @unlink($file_path);
            }
        }
        return $this;
    }

    /**
     * @deprecated
     * @param $file_name
     * @return null|string
     */
    public function getFilePath($file_name)
    {
        return !$file_name
            ? null
            : $this->getUploadDirPath() . '/' . $file_name;
    }

    /**
     * @deprecated
     * @param $file_name
     * @return null|string
     */
    public function getFileUrl($file_name)
    {
        return !$file_name
            ? null
            : '/' . $this->getUploadDirName() . '/' . $file_name;
    }

    /**
     * @deprecated
     * @throws \Exception
     * @return string
     */
    public function getUploadDirPath()
    {
        throw new \Exception('File upload using entity does not supported any more');
    }

    /**
     * @deprecated
     * @return string
     */
    protected function getUploadDirName()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'uploads';
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        $method = 'get' . str_replace(" ", "", ucwords(strtr($offset, "_-", "  ")));
        return method_exists($this, $method);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        $method = 'get' . str_replace(" ", "", ucwords(strtr($offset, "_-", "  ")));
        if(method_exists($this, $method)){
            return $this->$method();
        }
        return null;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $method = 'set' . str_replace(" ", "", ucwords(strtr($offset, "_-", "  ")));
        if(method_exists($this, $method)){
            $this->$method($value);
        }
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        $method = 'set' . str_replace(" ", "", ucwords(strtr($offset, "_-", "  ")));
        if(method_exists($this, $method)){
            $this->$method(null);
        }
    }

    /**
     * @param $name
     * @return mixed
     */
    function __get($name)
    {

        if(strpos($name, '(') !== false){

            list($property, $argumentsList) = explode('(', $name);

            $method = 'get' . str_replace(" ", "", ucwords(strtr($property, "_-", "  ")));
            $arguments = explode(',', str_replace(')', '', $argumentsList));

            if(method_exists($this, $method)){
                return call_user_func_array(array($this, $method), $arguments);
            } else {
                return null;
            }

        } else {
            return $this->offsetGet($name);
        }
    }

    /**
     * @return string
     */
    function __toString()
    {
        return (string)$this->getName();
    }

}
