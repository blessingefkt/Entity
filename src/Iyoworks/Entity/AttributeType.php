<?php namespace Iyoworks\Entity;

class AttributeType extends AttributeEnum
{
    protected static $attTypeInstance;

    public static function __callStatic($method, $args)
    {
        if (!isset(static::$attTypeInstance))
            static::$attTypeInstance = new AttributeType;

        return call_user_func_array([static::$attTypeInstance, $method], $args);
    }

}