<?php namespace Iyoworks\Entity;

use DateTime;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;

/**
 * Class BaseProtectedEntity
 * @package Iyoworks\Entity
 */
abstract class BaseProtectedEntity extends BaseEntity {

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
    protected $timestamps = true;
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
        return $this->guarded == array('*');
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
     * @return array
     */
    public function getTransformations()
    {
        if ($this->timestamps)
            $dates = [self::CREATED_AT => 'timestamp', self::UPDATED_AT => 'timestamp'];
        else
            $dates = [];
        if ($this->softDeletes)
            $dates[self::DELETED_AT] = 'timestamp';
        $this->transforms = array_merge($dates, $this->transforms);
        return $this->transforms;
    }

    /**
     * @return array
     */
    public function getDefaultAttributes()
    {
        $atts = $this->defaults;
        if ($this->timestamps)
        {
            $atts[self::CREATED_AT] = new DateTime();
            $atts[self::UPDATED_AT] = new DateTime();
        }
        if ($this->softDeletes) $atts[self::DELETED_AT] = null;
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