<?php namespace Iyoworks\Entity;


trait CachableMutatorsTrait {

    /**
     * The cache of the mutated attributes for each class.
     * @var array
     */
    protected static $mutatorCache = array();


    protected static function getMutatorSuffix()
    {
        return 'Attribute';
    }

    /**
     * @param string $class
     */
    protected static function cacheMutators($class)
    {
        $getPattern = 'get(.+)'.static::MUTATOR_SUFFIX;
        $setPattern = 'set(.+)'.static::MUTATOR_SUFFIX;
        // Here we will extract all of the mutated attributes so that we can quickly
        // spin through them after we export entities to their array form, which we
        // need to be fast. This will let us always know the attributes mutate.
        foreach (get_class_methods($class) as $method)
        {
            if (preg_match("/^{$getPattern}$/", $method, $matches))
            {
                static::$mutatorCache[$class]['getters'][] = lcfirst($matches[1]);
            }
            elseif (preg_match("/^{$setPattern}$/", $method, $matches))
            {
                static::$mutatorCache[$class]['setters'][] = lcfirst($matches[1]);
            }
        }
    }

    /**
     * @return array
     */
    public function allGetMutators()
    {
        if (isset(static::$mutatorCache[get_class($this)]['getters']))
            return static::$mutatorCache[get_class($this)]['getters'];
        return [];
    }
    /**
     * @return array
     */
    public function allSetMutators()
    {
        if (isset(static::$mutatorCache[get_class($this)]['getters']))
            return static::$mutatorCache[get_class($this)]['setters'];
        return [];
    }

    /**
     * @return bool
     */
    public function hasMutators()
    {
        return isset(static::$mutatorCache[get_class($this)]);
    }

    /**
     * Determine if a get mutator exists for an attribute.
     * @param  string  $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
        return method_exists($this, 'get'.studly_case($key).static::MUTATOR_SUFFIX);
    }

    /**
     * Determine if a set mutator exists for an attribute.
     * @param  string  $key
     * @return bool
     */
    public function hasSetMutator($key)
    {
        return method_exists($this, 'set'.studly_case($key).static::MUTATOR_SUFFIX);
    }

    /**
     * @return array
     */
    public static function getCachedMutators()
    {
        return static::$mutatorCache;
    }
} 