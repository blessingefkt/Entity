<?php namespace Iyoworks\Entity;
use ArrayAccess;
use DateTime;
use InvalidArgumentException;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;

/**
 * Class BaseAttributeEntity
 * @package Iyoworks\Entity
 */
abstract class BaseAttributeEntity extends BaseEntity
    implements ArrayAccess, ArrayableInterface, JsonableInterface {
    use CachableAttributesTrait;
    use AttributeSupportTrait;

    /**
     * @const string
     */
    const CREATED_AT = 'created_at';
    /**
     * @const string
     */
    const UPDATED_AT = 'updated_at';
    /**
     * @var bool
     */
    protected $strict = false;
    /**
     * @var bool
     */
    protected $usesTimestamps = true;
    /**
     * @var array
     */
    protected $attributeDefinitions = [];
    /**
     * @var array|null
     */
    protected $guarded;
    /**
     * @var array|null
     */
    protected $visible;

	/**
	 * Indicates if all mass assignment is enabled.
	* @var bool
	 */
	protected static $unguarded = false;

	/**
	 * The "booting" method of the entity.
     * @param BaseEntity $entity
	 * @return void
	 */
	protected static function boot($entity)
	{
        $class = get_class($entity);
        static::cacheAttributeDefinitions($class, $entity->getRawAttributeDefinitions());
		static::cacheMutators($class);
	}

	/**
	 * Get attribute values
	 * @return array
	 */
	public function getDefaultAttributeValues()
	{
		$defs = $this->getAttributeDefinitions();
		$defaults = [];
		foreach ($defs as $key => $def) {
			$defaults[$key] = AttributeType::get($def, null);
		}
		return $defaults;
	}

	/**
	 * Creates a new entity from the query builder result
	 * @param  array  $result
	 * @return \Iyoworks\Entity\BaseEntity
	 */
	public function buildNewInstance($result)
	{
		static::unguard();
		$inst = parent::buildNewInstance($result);
		static::reguard();
		return $inst;
	}

	/**
	 * Set the entity's attibutes
	 * @param  array  $attributes
	 * @return BaseEntity
	 * @throws MassAssignmentException;
	 */
	public function fill(array $attributes)
	{
		ksort($attributes);

		foreach ($attributes as $key => $value) {
			if (static::$unguarded  or ( $this->isAttribute($key) and !$this->isGuarded($key) ))
			{
				$this->setAttribute($key, $value);
			}
			elseif ($this->totallyGuarded()) {
				throw new MassAssignmentException($key);
			}
		}
		return $this;
	}

	/**
	 * Get a plain attribute (not a entities).
	 * @param  string  $key
	 * @return mixed
	 */
	protected function getAttributeValue($key)
	{
		$value = $this->getAttributeFromArray($key);

		if($this->isDefinedAttribute($key))
		{
			$value = AttributeType::get($this->getAttributeDefinition($key), $value);
		}

		// If the attribute has a get mutator, we will call that then return what
		// it returns as the value, which is useful for transforming values on
		// retrieval from the entity to a form that is more useful for usage.
		if ($this->hasGetMutator($key))
		{
			return $this->mutateAttribute($key, $value);
		}

		return $value;
	}

	/**
	 * Get an attribute from the entity.
	 * @param  string  $key
	 * @return mixed
	 */
	public function getAttribute($key)
	{
		$inAttributes = array_key_exists($key, $this->attributes);
		// If the key references an attribute, we can just go ahead and return the
		// plain attribute value from the entity. This allows every attribute to
		// be dynamically accessed through the _get method without accessors.
		if ($inAttributes || $this->isEntity($key) || $this->hasGetMutator($key))
		{
			return $this->getAttributeValue($key);
		}

		if (!$this->isDefinedAttribute($key))
			return $this->getUndefinedAttribute($key);

		// If the value has not been set, check if it has a valid attribute type
		// if so, get the default value for the type
		return AttributeType::get($this->getAttributeDefinition($key), null);
	}

    /**
     * @param string $key
     * @throws \InvalidArgumentException
     */
    protected function getUndefinedAttribute($key)
	{
		if ($this->strict)
			throw new InvalidArgumentException($key);
	}

	/**
	 * Set a given attribute on the entity.
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function setAttribute($key, $value)
	{
		if($this->isDefinedAttribute($key))
			$value = AttributeType::set($this->getAttributeDefinition($key), $value);

		// First we will check for the presence of a mutator for the set operation
		// which simply lets the developers tweak the attribute as it is set on
		// the entity, such as "json_encoding" an listing of data for storage.
		if ($this->hasSetMutator($key))
		{
			return $this->mutateAttributeSetter($key, $value);
		}

		if (!$this->isDefinedAttribute($key))
			return $this->setUndefinedAttribute($key, $value);

		$this->attributes[$key] = $value;
	}

    /**
     * @param string $key
     * @param mixed $value
     * @throws \InvalidArgumentException
     */
    protected function setUndefinedAttribute($key, $value)
	{
		if ($this->strict)
			throw new InvalidArgumentException($key);
		$this->attributes[$key] = $value;
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
	 * Determine if an attribute exists
	 * @param  string  $key
	 * @return boolean
	 */
	public function isEntity($key)
	{
		return $this->attributeTypeMatches($key, AttributeType::Entity);
	}

	/**
	 * Get raw attribute definitions
	 * @return array
	 */
	public function getRawAttributeDefinitions()
	{
		if($this->usesTimestamps)
		{
			$this->attributeDefinitions[static::CREATED_AT] = AttributeType::Timestamp;
			$this->attributeDefinitions[static::UPDATED_AT] = AttributeType::Timestamp;
		}
		return $this->attributeDefinitions;
	}

	/**
	 * Get an attribute array of all visible attributes.
	 * @return array
	 */
	protected function getVisibleAttributes()
	{
		if (count($this->getVisibleKeys()) > 0)
		{
			return array_intersect_key($this->attributes, array_flip($this->getVisibleKeys()));
		}
		return $this->attributes;
	}

    /**
     * Get the keys of all attributes that are visible
     * @return array
     */
    protected function getVisibleKeys()
    {
        if (is_null($this->visible))
        {
            foreach ($this->getAttributeDefinitions() as $key => $def)
            {
                if ($def['visible']) $this->visible[$key] = $key;
            }
        }
        return $this->visible;
    }

    /**
     * Get the keys of all attributes that are guarded
     * @return array
     */
    protected function getGuardedKeys()
    {
        if (is_null($this->guarded))
        {
            foreach ($this->getAttributeDefinitions() as $key => $def)
            {
                if ($def['guarded']) $this->guarded[$key] = $key;
            }
        }
        return $this->guarded;
    }

    /**
     * Convert attributes to strings
     * @param array $attributes
     * @return array
     */
    protected function processArray(array $attributes)
    {
        foreach ($attributes as $key => &$value)
        {
            if($this->isDateType($key) and $value instanceof \DateTime)
            {
                $format = $this->getAttributeDefinition($key)['format'];
                $value = $value->format($format);
            }
        }
        return $attributes;
    }

	/**
	 * Determine if the given key is guarded.
	 * @param  string  $key
	 * @return bool
	 */
	public function isGuarded($key)
	{
		return $this->isGuardedAttribute($key) or $this->totallyGuarded();
	}

	/**
	 * Determine if the entity is totally guarded.
	 * @return bool
	 */
	public function totallyGuarded()
	{
		return $this->guarded == array('*') || $this->allAttributesGuarded();
	}

    /**
     * Convert the entity instance to an array.
     * @return array
     */
    public function attributeArray()
    {
        $attributes = $this->attributes;

        // We want to spin through all the mutated attributes for this entity and call
        // the mutator for the attribute. We cache off every mutated attributes so
        // we don't have to constantly check on attributes that actually change.

        foreach ($this->getMutatedAttributes() as $key)
        {
            if (! array_key_exists($key, $attributes)) continue;

            $attributes[$key] = $this->mutateAttribute($key, $attributes[$key]);
        }

        return $this->processArray($attributes);
    }

    /**
     * Convert the all visbile attributes to an array.
     * @return array
     */
    public function toArray()
    {
        $attributes = $this->getVisibleAttributes();

        // We want to spin through all the mutated attributes for this entity and call
        // the mutator for the attribute. We cache off every mutated attributes so
        // we don't have to constantly check on attributes that actually change.

        foreach ($this->getMutatedAttributes() as $key)
        {
            if (! array_key_exists($key, $attributes)) continue;

            $attributes[$key] = $this->mutateAttribute($key, $attributes[$key]);
        }

        return $this->processArray($attributes);
    }

	/******************************************
	*** Static Methods 
	*****************************************/

	/**
	 * Disable all mass assignable restrictions.
	 * @return void
	 */
	public static function unguard()
	{
		static::$unguarded = true;
	}

	/**
	 * Enable the mass assignment restrictions.
	 * @return void
	 */
	public static function reguard()
	{
		static::$unguarded = false;
	}
}