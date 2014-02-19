<?php namespace Iyoworks\Entity;

use ArrayAccess;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;
use InvalidArgumentException;

/**
 * Class BaseEntity
 * @package Iyoworks\Entity
 */
abstract class BaseEntity implements ArrayAccess, ArrayableInterface, JsonableInterface {
    use CachableMutatorsTrait;
    /**
     * @const string
     */
    const MUTATOR_SUFFIX = 'Attribute';

    protected static $booted = [];

    /**
     * transformation rules.
     * @var array
     */
    protected $transforms = [];

    /**
     * @var array
     */
    protected $attributes = [];
    /**
     * @var array
     */
    protected $original = [];
    /**
     * @var bool
     */
    public $exists = true;

    /**
     * Create a new instance
     * @param  array $attributes
     * @param bool $exists
     */
	public function __construct(array $attributes = [], $exists = false)
	{
        if (!isset(static::$booted[$_class = get_class($this)]))
        {
            static::boot($this);
            static::$booted[$_class] = true;
        }
        $this->exists = $exists;
		$this->fill(array_merge($this->getDefaultAttributes(), $attributes));
	}

	/**
	 * Create a new instance
	 * @param  array   $attributes
	 * @return \Iyoworks\Entity\BaseEntity
	 */
	public function newInstance(array $attributes = array())
	{
		return new static($attributes);
	}

    /**
     * Create a new instance
     * @param  array   $attributes
     * @return \Iyoworks\Entity\BaseEntity
     */
    public static function make(array $attributes = [])
    {
        return with(new static)->newInstance($attributes);
    }

    /**
     * The "booting" method of the entity.
     * @param BaseEntity $entity
     * @return void
     */
	protected static function boot($entity)
	{

	}

	/**
	 * Get attribute values
	 * @return array
	 */
	public function getDefaultAttributes()
	{
		return [];
	}

    /**
     * Creates a new entity from the query builder result
     * @param  array $result
     * @param bool $exists
     * @return \Iyoworks\Entity\BaseEntity
     */
	public function buildInstance($result, $exists = true)
	{
		$inst = $this->newInstance();
		$inst->setRawAttributes((array) $result, true);
        $inst->exists = $exists;
		return $inst;
	}

	/**
	 * Set the entity's attributes
	 * @param  array  $attributes
	 * @return BaseEntity
	 */
	public function fill(array $attributes)
	{
		foreach ($attributes as $key => $value)
            $this->setAttribute($key, $value);
		return $this;
	}

	/**
	 * Get an attribute from the $attributes array.
	 * @param  string  $key
	 * @return mixed
	 */
	protected function getAttributeFromArray($key)
	{
		if (array_key_exists($key, $this->attributes))
            return $this->attributes[$key];
	}

	/**
	 * Get an attribute
	 * @param  string  $key
	 * @return mixed
	 */
	protected function getAttributeValue($key)
	{
		$value = $this->getAttributeFromArray($key);

		if ($this->hasGetMutator($key))
            return $this->mutateAttribute($key, $value);

		return $this->transformer()->build($key, $value);
	}

	/**
	 * Get an attribute from the entity.
	 * @param  string  $key
	 * @return mixed
	 */
	public function getAttribute($key)
	{
		$inAttributes = array_key_exists($key, $this->attributes);

		if ($inAttributes || $this->hasGetMutator($key))
            return $this->getAttributeValue($key);

        if (!$this->isDefinedAttribute($key))
            if ($this->strict) $this->undefinedAttributeError($key);

        return $this->transformer()->build($key, null);
	}

    /**
     * Set a given attribute on the entity.
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function setAttribute($key, $value)
    {
        if ($this->hasSetMutator($key))
            return $this->mutateAttributeSetter($key, $value);

        if (!$this->isDefinedAttribute($key))
            if ($this->strict) $this->undefinedAttributeError($key);

        $this->attributes[$key] = $value;
    }

	/**
	 * Get the value of an attribute using its mutator.
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return mixed
	 */
	protected function mutateAttribute($key, $value)
	{
		return $this->{'get'.studly_case($key).static::MUTATOR_SUFFIX}($value);
	}

	/**
	 * Get the value of an attribute using its mutator.
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return mixed
	 */
	protected function mutateAttributeSetter($key, $value)
	{
		return $this->{'set'.studly_case($key).static::MUTATOR_SUFFIX}($value);
	}

	/**
	 * Determine if a given attribute is dirty.
	 * @param  string  $attribute
	 * @return bool
	 */
	public function isDirty($attribute = null)
	{
		if($attribute)
			return array_key_exists($attribute, $this->getDirty());
		return count($this->getDirty()) > 0;

	}

	/**
	 * Get the attributes that have been changed since last sync.
	 * @param string|null $attribute
	 * @return array|str
	 */
	public function getDirty($attribute = null)
	{
		if(empty($this->original))
            $dirty = $this->attributes;
        else
        {
            $dirty = array();
            foreach ($this->attributes as $key => $value)
            {
                $addToDirty =  !array_key_exists($key, $this->original) || $value !== $this->original[$key];
                if ($addToDirty) $dirty[$key] = $value;
            }
        }
        if ($attribute) return array_get($dirty, $attribute);
		return $dirty;
	}

	/**
	 * Clone the entity into a new, non-existing instance.
	 * @return \Iyoworks\Entity\BaseEntity
	 */
	public function replicate()
	{
        $attributes = $this->attributes;
        unset($attributes[$this->getKeyName()]);
		return $this->buildInstance($attributes, false);
	}

	/**
	 * Get all of the current attributes on the entity.
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	/**
	 * Set the array of entity attributes. No checking is done.
	 * @param  array  $attributes
	 * @param  bool   $sync
	 * @return void
	 */
	public function setRawAttributes(array $attributes, $sync = false)
	{
		$this->attributes = $attributes;
        if ($sync) $this->syncOriginal();
	}

	/**
	 * Get the entity's original attribute values.
	 * @param  string  $key 	get the original attribute value with key
	 * @param  mixed   $default
	 * @return array
	 */
	public function getOriginal($key = null, $default = null)
	{
		if($key) return array_get($this->original, $key, $default);
		return $this->original;
	}

	/**
	 * Sync the original attributes with the current.
	 * @return \Iyoworks\Entity\BaseEntity
	 */
	public function syncOriginal()
	{
		$this->original = $this->attributes;
		return $this;
	}

    /**
     * Spin through attributes and get values that have mutator methods
     * @param $attributes
     * @return mixed
     */
    protected function processArray($attributes)
    {
        foreach ($this->allGetMutators() as $key)
        {
            if (! array_key_exists($key, $attributes)) continue;

            $attributes[$key] = $this->mutateAttribute($key, $attributes[$key]);
        }

        return $attributes;
    }

    /**
     * Convert the entity instance to an array.
     * @return array
     */
    public function toArray()
    {
        return $this->processArray($this->attributes);
    }

    /**
     * @return Transformer
     */
    public function transformer()
    {
        return new Transformer($this->transforms);
    }

    /**
     * @return string[]
     */
    public function toStringArray()
    {
        return $this->transformer()->smashData($this->toArray());
    }

	/**
	 * Convert the entity instance to JSON.
	 * @param  int  $options
	 * @return string
	 */
	public function toJson($options = 0)
	{
		return json_encode($this->toArray(), $options);
	}

    /**
     * Checks if an attribute has a definition
     * @param  string
     * @return boolean
     */
    public function isDefinedAttribute($key)
    {
        return array_key_exists($key, $this->transforms);
    }

    /**
     * Determine if an attribute exists
     * @param  string  $key
     * @return boolean
     */
    public function isAttribute($key)
    {
        if(!$this->strict) return true;
        return $this->isDefinedAttribute($key);
    }

    /**
     * @param $key
     * @throws InvalidArgumentException
     */
    protected function undefinedAttributeError($key)
    {
        throw new InvalidArgumentException($key);
    }

    /**
     * Check if entity exists
     * @return bool
     */
    public function exists()
    {
        return $this->exists;
    }

    /**
     * Get the entity key name
     * @return string
     */
    public function getKeyName()
    {
        return 'id';
    }

    /**
     * Get the entity key
     * @return int|mixed
     */
    public function getKey()
    {
        return $this->{$this->getKeyName()};
    }

	/**
	 * Dynamically retrieve attributes on the entity.
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->getAttribute($key);
	}

	/**
	 * Dynamically set attributes on the entity.
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->setAttribute($key, $value);
	}

	/**
	 * Determine if the given attribute exists.
	 * @param  mixed  $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return isset($this->$offset);
	}

	/**
	 * Get the value for a given offset.
	 * @param  mixed  $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->$offset;
	}

	/**
	 * Set the value for a given offset.
	 * @param  mixed  $offset
	 * @param  mixed  $value
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		$this->$offset = $value;
	}

	/**
	 * Unset the value for a given offset.
	 * @param  mixed  $offset
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		unset($this->$offset);
	}

	/**
	 * Determine if an attribute exists on the entity.
	 * @param  string  $key
	 * @return bool
	 */
	public function __isset($key)
	{
		return isset($this->attributes[$key]);
	}

	/**
	 * Unset an attribute on the entity.
	 * @param  string  $key
	 * @return void
	 */
	public function __unset($key)
	{
		unset($this->attributes[$key]);
	}

	/**
	 * Convert the entity to its string representation.
	 * @return string
	 */
	public function __toString()
	{
		return $this->toJson();
	}
}