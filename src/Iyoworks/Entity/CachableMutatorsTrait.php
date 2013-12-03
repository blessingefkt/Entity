<?php namespace Iyoworks\Entity;


trait CachableMutatorsTrait {

    /**
     * The cache of the mutated attributes for each class.
     * @var array
     */
    protected static $mutatorCache = array();

    /**
     * @param string $class
     */
    protected static function cacheMutators($class)
    {
        // Here we will extract all of the mutated attributes so that we can quickly
        // spin through them after we export entities to their array form, which we
        // need to be fast. This will let us always know the attributes mutate.
        foreach (get_class_methods($class) as $method)
        {
            if (preg_match('/^get(.+)Attribute$/', $method, $matches))
            {
                static::$mutatorCache[$class][] = lcfirst($matches[1]);
            }
        }
    }

    /**
     * @return array
     */
    public function getMutators()
    {
        return static::$mutatorCache[get_class($this)];
    }

    /**
     * @return bool
     */
    public function hasMutators()
    {
        return isset(static::$mutatorCache[get_class($this)]);
    }

    /**
     * @return array
     */
    public static function getCachedMutators()
    {
        return static::$mutatorCache;
    }
} 