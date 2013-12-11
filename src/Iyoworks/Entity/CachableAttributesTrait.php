<?php namespace Iyoworks\Entity;

trait CachableAttributesTrait {
    /**
     * @var array
     */
    protected static $pivotKeys = [];

    /**
     * The cache of the attribute definitions for each class.
     * @var array
     */
    protected static $attributeDefinitionsCache = [];

    protected static function cacheAttributeDefinitions($group, array $defs)
    {
        foreach ($defs as $attr => $def) {
            $defs[$attr] = Attribute::getFullDefinition($def);
            if ($def['type'] == Attribute::Entity)
            {
                static::$pivotKeys[$group][$attr] = $def['pivots'];
            }
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
     * @param $attr
     * @return bool
     */
    public function isPivot($attr)
    {
        foreach (static::$pivotKeys[get_class($this)] as $key => $pivot) {
            if ($pivot == $attr) return true;
        }
        return false;
    }

    /**
     * @param $attr
     * @return string|null
     */
    public function getPivotOwner($attr)
    {
        foreach (static::$pivotKeys[get_class($this)] as $key => $pivot) {
            if ($pivot == $attr) return $key;
        }
        return null;
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