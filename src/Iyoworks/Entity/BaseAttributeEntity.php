<?php namespace Iyoworks\Entity;

use DateTime;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;
use InvalidArgumentException;

/**
 * Class BaseAttributeEntity
 * @package Iyoworks\Entity
 */
abstract class BaseAttributeEntity extends BaseEntity {

    /**
     * @const string
     */
    const CREATED_AT = 'created_at';
    /**
     * @const string
     */
    const UPDATED_AT = 'updated_at';
    /**
     * @const string
     */
    const DELETED_AT = 'deleted_at';
    /**
     * @var bool
     */
    protected $strict = false;
    /**
     * @var bool
     */
    protected $usesTimestamps = true;
    /**
     * @var bool
     */
    protected $softDeletes = false;
    /**
     * @var array
     */
    protected $guarded = [];
    /**
     * @var array
     */
    protected $visible = [];

    /**
     * Indicates if all mass assignment is enabled.
     * @var bool
     */
    protected static $unguarded = false;

    /**
     * @inheritdoc
     */
    protected static function boot($entity)
    {
        $class = get_class($entity);
        static::cacheMutators($class);
    }

    /**
     * @inheritdoc
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if ($this->canFill($key))
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
     * Determine if the given key is guarded.
     * @param  string  $key
     * @return bool
     */
    public function isGuarded($key)
    {
        return in_array($key, $this->guarded) || $this->totallyGuarded();
    }

    /**
     * Determine if the entity is totally guarded.
     * @return bool
     */
    public function totallyGuarded()
    {
        return $this->guarded == array('*')
            || count(array_diff_key($this->transforms, $this->guarded)) > 0;
    }

    /**
     * Determine if an attribute can be filled.
     * @param $key
     * @return bool
     */
    public function canFill($key)
    {
        return static::$unguarded  or ($this->isAttribute($key) and !$this->isGuarded($key));
    }

    /**
     * Get default attribute values
     * @return array
     */
    public function getDefaultAttributes()
    {
        $atts = [];
        if ($this->usesTimestamps)
            $atts = [self::CREATED_AT => 'datetime', self::UPDATED_AT => 'datetime'];
        if ($this->softDeletes)
            $atts[self::DELETED_AT] = 'datetime';
        return $atts;
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        if (!empty($this->visible))
        {
            $attributes = [];
            foreach ($this->visible as $key)
                 $attributes[$key] = $this->attributes[$key];
            return $this->processArray($attributes);
        }
        return $this->processArray($this->attributes);
    }

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