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
    protected static function cacheMutators($class, $snakeCaseAttributes = true)
    {
        $getPattern = 'get(.+)' . static::getMutatorSuffix();
        $setPattern = 'set(.+)' . static::getMutatorSuffix();
        // Here we will extract all of the mutated attributes so that we can quickly
        // spin through them after we export entities to their array form, which we
        // need to be fast. This will let us always know the attributes mutate.
        foreach (get_class_methods($class) as $method)
        {
            if (preg_match("/^{$getPattern}$/", $method, $matches))
            {
                $attr = $snakeCaseAttributes ? snake_case($matches[1]) : lcfirst($matches[1]);
                static::$mutatorCache[$class]['getters'][$attr] = lcfirst($matches[1]);
            }
            elseif (preg_match("/^{$setPattern}$/", $method, $matches))
            {
                $attr = $snakeCaseAttributes ? snake_case($matches[1]) : lcfirst($matches[1]);
                static::$mutatorCache[$class]['setters'][$attr] = lcfirst($matches[1]);
            }
        }
    }

    /**
     * @return array
     */
    public static function getCachedMutators()
    {
        return static::$mutatorCache;
    }

    /**
     * @return array
     */
    public function allGetMutators()
    {
        if (isset(static::$mutatorCache[get_class($this)]['getters']))
        {
            return static::$mutatorCache[get_class($this)]['getters'];
        }
        return [];
    }

    /**
     * @return array
     */
    public function allSetMutators()
    {
        if (isset(static::$mutatorCache[get_class($this)]['setters']))
        {
            return static::$mutatorCache[get_class($this)]['setters'];
        }
        return [];
    }

    /**
     * Get the mutated attributes for a given instance.
     * @return array
     */
    public function allMutators()
    {
        $mutators = array_merge($this->allGetMutators(), $this->allSetMutators());
        ksort($mutators);
        return $mutators;
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
     * @param  string $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
        return method_exists($this, 'get' . studly_case($key) . static::getMutatorSuffix());
    }

    /**
     * Determine if a set mutator exists for an attribute.
     * @param  string $key
     * @return bool
     */
    public function hasSetMutator($key)
    {
        return method_exists($this, 'set' . studly_case($key) . static::getMutatorSuffix());
    }

    /**
     * Get the value of an attribute using its mutator.
     * @param  string $key
     * @param  mixed $value
     * @return mixed
     */
    protected function mutateAttribute($key, $value)
    {
        return $this->{'get' . studly_case($key) . static::getMutatorSuffix()}($value);
    }

    /**
     * Get the value of an attribute using its mutator.
     * @param  string $key
     * @param  mixed $value
     * @return mixed
     */
    protected function mutateAttributeSetter($key, $value)
    {
        return $this->{'set' . studly_case($key) . static::getMutatorSuffix()}($value);
    }
} 