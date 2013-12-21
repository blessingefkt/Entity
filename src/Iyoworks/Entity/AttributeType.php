<?php namespace Iyoworks\Entity;

use DateTime;
use Illuminate\Support\Collection;
use Iyoworks\Support\Str;

class AttributeType extends AttributeEnum {
    const DEFAULT_COLLECTION_CLASS = 'Illuminate\Support\Collection';
    const DEFAULT_ENTITY_CLASS = 'stdClass';
    /**
     * @var bool
     */
    protected static $booted = false;
    /**
     * @var array
     */
    protected $baseDefinition = [
        'guarded' => null,
        'visible' => true,
        'type' => AttributeType::Mixed
    ];
    /**
     * @var array
     */
    static $defaultDefinitions = [
        AttributeType::PK => [
            'guarded' => true
        ],
        AttributeType::Entity => [
            'key' => null,
            'class' => self::DEFAULT_ENTITY_CLASS,
            'many' => false,
            'pivot_data' => [],
            'collection' => self::DEFAULT_COLLECTION_CLASS,
            'indexKey' => 'id'
        ],
        AttributeType::Json => [
            'force' => false
        ],
        AttributeType::Timestamp => [
            'format' => 'Y-m-d H:i:s'
        ],
        AttributeType::UID => [
            'prefix' => null,
            'length' => 36,
            'pool' => Str::ALPHA_NUM,
            'auto' => false
        ]
    ];

    public function __construct()
    {
        if (!static::$booted)
        {
            static::boot();
            static::$booted = true;
        }
    }

    protected static function boot() { }

    /**
     * @param $type
     * @param $value
     * @return mixed
     */
    public function set($type, $value)
    {
        $def = $this->getFullDefinition($type);
        $type = $def['type'];
        $method = 'set'.studly_case($type);
        if(method_exists(get_called_class(), $method))
            return $this->$method($value, $def);
        return $value;
    }

    /**
     * @param $type
     * @param $value
     * @return mixed
     */
    public function get($type, $value)
    {
        $def = $this->getFullDefinition($type);
        $type = $def['type'];
        $method = 'get'.studly_case($type);
        if(method_exists(get_called_class(), $method))
            return $this->$method($value, $def);
        return $value;
    }

    /**
     * @param $type
     * @return bool
     */
    public function isValidType($type)
    {
        return array_key_exists($type, $this->toArray()) ||
        (array_search($type, $this->toArray(), true) !== false);
    }

    /**
     * Checks if an attribute is a date type
     * @param  string
     * @return boolean
     */
    public function isDateType($type)
    {
        return in_array($type, [static::DateTime, static::Timestamp]);
    }

    /**
     * @return array
     */
    public function toArray(){
        static $reflection;
        if(is_null($reflection)) $reflection = new \ReflectionClass($this);
        return $reflection->getConstants();
    }

    /**
     * @param $value
     * @param array $def
     * @return int
     */
    public function setInteger($value, array $def)
    {
        return (int) $value;
    }

    /**
     * @param $value
     * @param array $def
     * @return int
     */
    public function getInteger($value, array $def)
    {
        return (int) $value;
    }

    /**
     * @param $value
     * @param array $def
     * @return bool
     */
    public function setBoolean($value, array $def)
    {
        return (bool) $value;
    }

    /**
     * @param $value
     * @param array $def
     * @return bool
     */
    public function getBoolean($value, array $def)
    {
        return (bool) $value;
    }

    /**
     * @param $value
     * @param array $def
     * @return float
     */
    public function setDouble($value, array $def)
    {
        return (double) $value;
    }

    /**
     * @param $value
     * @param array $def
     * @return float
     */
    public function getDouble($value, array $def)
    {
        return (double) $value;
    }

    /**
     * @param $value
     * @param array $def
     * @return object
     */
    public function setEntity($value, array $def)
    {
        $class = $def['class'];
        if (is_array($value) and is_subclass_of($class, 'Iyoworks\Entity\BaseEntity'))
        {
            if ($def['many'])
            {
                $collection = $this->makeCollection($def['collection']);

                foreach ($value as $_ent)
                {
                    $collection[ $_ent[ $def['indexKey'] ] ] = $this->buildEntity($class, $_ent);
                }
                return $collection;
            }

            return $this->buildEntity($class, $value);
        }
        return $value;
    }

    /**
     * @param $value
     * @param array $def
     * @return object
     */
    public function getEntity($value, array $def)
    {
        if(is_null($value))
        {
            if ($def['many']) return $this->makeCollection($def['collection']);
            return $this->makeClass($def['class']);
        }
        return $value;
    }

    /**
     * @param $value
     * @param array $def
     * @return float
     */
    public function setFloat($value, array $def)
    {
        return (float) $value;
    }

    /**
     * @param $value
     * @param array $def
     * @return float
     */
    public function getFloat($value, array $def)
    {
        return (float) $value;
    }

    /**
     * @param $value
     * @param array $def
     * @return mixed
     */
    public function setHandle($value, array $def)
    {
        return handle($value);
    }

    /**
     * @param $value
     * @param array $def
     * @return string
     */
    public function setJson($value, array $def)
    {
        if($value instanceof \Illuminate\Support\Contracts\JsonableInterface)
            $output = $value->toJson();
        elseif(!is_string($value) or $def['force'])
            $output = json_encode($value ?: []);
        else
            $output = $value;
        return $output;
    }

    /**
     * @param $value
     * @param array $def
     * @return array|mixed
     */
    public function getJson($value, array $def)
    {
        if (empty($value) or $value == 'null')
            $output = [];
        else
            $output = json_decode($value, 1);

        return $output;
    }

    /**
     * @param $value
     * @param array $def
     * @return int
     */
    public function setPrimaryKey($value, array $def)
    {
        return  (int) $value;
    }

    /**
     * @param $value
     * @param array $def
     * @return mixed
     */
    public function getPrimaryKey($value, array $def)
    {
        return $value;
    }

    /**
     * @param $value
     * @param array $def
     * @return string
     */
    public function setSerial($value, array $def)
    {
        return  serialize($value);
    }

    /**
     * @param $value
     * @param array $def
     * @return mixed
     */
    public function getSerial($value, array $def)
    {
        return unserialize($value);
    }

    /**
     * @param $value
     * @param array $def
     * @return mixed
     */
    public function setSlug($value, array $def)
    {
        return slugify($value);
    }

    /**
     * @param $value
     * @param array $def
     * @return string
     */
    public function setString($value, array $def)
    {
        return (string) $value;
    }

    /**
     * @param $value
     * @param array $def
     * @return string
     */
    public function getString($value, array $def)
    {
        return (string)  $value;
    }

    /**
     * @param $value
     * @param array $def
     * @return string
     */
    public function setTimestamp($value, array $def)
    {
        if(is_string($value))
        {
            $date = $this->newDateObject($value);
            return $date->format($def['format']);
        }
        return $value;
    }

    /**
     * @param $value
     * @param array $def
     * @return DateTime
     */
    public function getTimestamp($value, array $def)
    {
        if (is_null($value)) return $this->newDateObject();

        if (is_numeric($value))
        {
            return $this->newDateFromTimestamp($value);
        }
        elseif (is_string($value) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value))
        {
            return $this->newDateFromFormat($value, 'Y-m-d');
        }
        elseif ($value instanceof \DateTime)
        {
            return $value;
        }

        return $this->newDateFromFormat($value, $def['format']);
    }

    /**
     * @param $value
     * @param array $def
     * @return mixed
     */
    public function getUid($value, array $def)
    {
        if($def['auto'] and is_null($value))
            return Str::superRandom($def['length'], $def['prefix'], $def['pool']);
        return $value;
    }

    /**
     * @param $value
     * @param array $def
     * @return mixed
     */
    public function setUid($value, array $def)
    {
        return $value;
    }

    /**
     * Convert definition to appropriate array
     * @param  str|array $definition
     * @return array
     */
    public function getFullDefinition($definition)
    {
        $definition = $this->resolveType($definition);

        $defaults = array_get(static::$defaultDefinitions, $definition['type'], []);

        return array_merge($this->baseDefinition, $defaults, $definition);
    }

    /**
     * @param mixed $definition
     * @return array
     */
    protected function resolveType($definition)
    {
        if(is_array($definition))
        {
            if (!isset($definition['type']))
                $definition['type'] = array_shift($definition);
        }
        else
            $definition = ['type' => $definition];
        return $definition;
    }

    /**
     * @return array
     */
    public function getBaseDefinition()
    {
        return $this->baseDefinition;
    }

    /**
     * @param $class
     * @param $data
     * @return mixed
     */
    protected function buildEntity($class, $data)
    {
        return with( new $class )->buildNewInstance($data);
    }

    /**
     * @param null $value
     * @return DateTime
     */
    protected function newDateObject($value = null)
    {
        if($value) return new DateTime($value);
        return new DateTime;
    }

    /**
     * @param $value
     * @return DateTime
     */
    protected function newDateFromTimestamp($value)
    {
        $date = new DateTime;
        $date->setTimestamp($value);
        return $date;
    }

    /**
     * @param $value
     * @param $format
     * @return DateTime
     */
    protected function newDateFromFormat($value, $format)
    {
        return new DateTime($value);
    }

    /**
     * @param string|null $class
     * @return object
     */
    protected function makeCollection($class = null)
    {
        $class = $class ?: self::DEFAULT_COLLECTION_CLASS;
        return $this->makeClass($class);
    }

    /**
     * @param $class
     * @param array $args
     * @return object
     */
    protected function makeClass($class, array $args = [])
    {
        $classParts = explode(',', $class);
        $className = array_shift($classParts);
        $reflection = new \ReflectionClass($className);
        $methods = [];
        foreach($classParts as $k => $_arg)
        {
            if (str_contains($_arg, ':'))
            {
                $_aparts = explode(':', $_arg);
                $methods[$_aparts[0]] = $_aparts[1];
                unset($classParts[$k]);
            }
        }
        if ($args || $classParts)
            $args = array_merge($classParts, $args);
        $obj = $reflection->newInstance($args);

        foreach($methods as $method => $val)
        {
            $obj->$method($val);
        }
        return $obj;
    }
}