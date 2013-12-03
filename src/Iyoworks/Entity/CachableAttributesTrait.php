<?php namespace Iyoworks\Entity;

trait CachableAttributesTrait {

    /**
     * The cache of the attribute definitions for each class.
     * @var array
     */
    protected static $attributeDefinitionsCache = [];

    protected static function cacheAttributeDefinitions($group, array $defs)
    {
        foreach ($defs as $attr => $def) {
            $defs[$attr] = Attribute::getFullDefinition($def);
        }
        if(isset(static::$attributeDefinitionsCache[$group]))
            $defs = array_replace_recursive(static::$attributeDefinitionsCache[$group], $defs);
        static::$attributeDefinitionsCache[$group] = $defs;
    }

    /**
     * Get attribute definitions
     * @return array
     */
    public function getAttributeDefinitions()
    {
        return array_get(static::$attributeDefinitionsCache, get_class($this), []);
    }

    /**
     * Get all cached attributes
     * @return array
     */
    public static function getCachedAttributeDefinitions()
    {
        return static::$attributeDefinitionsCache;
    }
} 