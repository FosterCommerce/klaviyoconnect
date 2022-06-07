<?php
namespace fostercommerce\klaviyoconnect\models;

use Craft;
use craft\base\Model;
use yii\base\UnknownPropertyException;

abstract class Base extends Model
{
    /**
     * @var		array	$customAttributes
     */
    private array $customAttributes = [];

    /**
     * __set.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	mixed	$name 	
     * @param	mixed	$value	
     * @return	void
     */
    public function __set($name, $value): void
    {
        try {
            parent::__set($name, $value);
        } catch (UnknownPropertyException $e) {
            $this->$name = $value;
            $this->customAttributes[] = $name;
        }
    }

    /**
     * setCustomProperties.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	mixed	$properties	
     * @return	void
     */
    public function setCustomProperties($properties): void
    {
        foreach ($properties as $property => $value) {
            $this->$property = $value;
        }
    }

    /**
     * toArray.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	array  	$fields   	Default: []
     * @param	array  	$expand   	Default: []
     * @param	boolean	$recursive	Default: true
     * @return	mixed
     */
    public function toArray(array $fields = [], array $expand = [], $recursive = true): mixed
    {
        $arr = parent::toArray($fields, $expand, $recursive);

        $mapped = [];
        foreach ($arr as $name => $value) {
            $mapped["\${$name}"] = $value;
        }

        foreach ($this->customAttributes as $name) {
            $mapped[$name] = $this->$name;
        }

        return $mapped;
    }
}
