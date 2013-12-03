<?php namespace Iyoworks\Entity;

/**
 * Class Attribute
 * @package Iyoworks\Entity
 */
class Attribute extends AttributeEnum
{
    /**
     * @var
     */
    protected static $instance;

    /**
     * @return AttributeType
     */
    public static function getInstance()
    {
        if (!isset(static::$instance))
            static::$instance = new AttributeType;

        return static::$instance;
    }

    /**
     * @param AttributeType $instance
     */
    public static function setAttributeInstance($instance)
    {
        static::$instance = $instance;
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        return call_user_func_array([static::getInstance(), $method], $args);
    }

}