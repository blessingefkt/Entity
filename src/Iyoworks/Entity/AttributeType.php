<?php namespace Iyoworks\Entity;

use DateTime;
use Iyoworks\Support\Str;

class AttributeType extends AttributeEnum {

    protected $baseDefinition = ['guarded' => null, 'visible' => true];

    static $defaultDefinitions = [
        AttributeType::PK => [
            'guarded' => true
        ],
        AttributeType::Entity => [
            'class' => null,
            'many' => false
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

	public function set($type, $value)
	{
		$def = $this->getFullDefinition($type);
		$type = $def['type'];
		$method = 'set'.studly_case($type);
		if(method_exists(get_called_class(), $method))
			return $this->$method($value, $def);
		return $value;
	}

    public function get($type, $value)
	{
		$def = $this->getFullDefinition($type);
		$type = $def['type'];
		$method = 'get'.studly_case($type);
		if(method_exists(get_called_class(), $method))
			return $this->$method($value, $def);
		return $value;
	}

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

    public function toArray(){
		static $reflection;
		if(is_null($reflection)) $reflection = new \ReflectionClass($this);
		return $reflection->getConstants();
	}

    public function setInteger($value, array $def)
	{
		return (int) $value;
	}

    public function getInteger($value, array $def)
	{
		return (int) $value;
	}

    public function setBoolean($value, array $def)
	{
		return (bool) $value;
	}

    public function getBoolean($value, array $def)
	{
		return (bool) $value;
	}

    public function setDouble($value, array $def)
	{
		return (double) $value;
	}

    public function getDouble($value, array $def)
	{
		return (double) $value;
	}

    public function getEntity($value, array $def)
	{
		if(is_null($value))
		{
			$class = $def['class'] ?: 'StdClass';
			return new $class;
		}
		return $value;
	}

    public function setEntity($value, array $def)
	{
		return $value;
	}

    public function setFloat($value, array $def)
	{
		return (float) $value;
	}

    public function getFloat($value, array $def)
	{
		return (float) $value;
	}

    public function setHandle($value, array $def)
	{
		return handle($value);
	}

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

    public function getJson($value, array $def)
	{
		if (empty($value) or $value == 'null')
			$output = [];
		else
			$output = json_decode($value, 1);

		return $output;
	}

    public function setPrimaryKey($value, array $def)
    {
        return  (int) $value;
    }

    public function getPrimaryKey($value, array $def)
    {
        return $value;
    }

    public function setSerial($value, array $def)
	{
		return  serialize($value);
	}

    public function getSerial($value, array $def)
	{
		return unserialize($value);
	}

    public function setSlug($value, array $def)
	{
		return slugify($value);
	}

    public function setString($value, array $def)
	{
		return (string) $value;
	}

    public function getString($value, array $def)
	{
		return (string)  $value;
	}

    public function setTimestamp($value, array $def)
	{
		if(is_string($value))
		{
			$date = $this->newDateObject($value);
			return $date->format($def['format']);
		}
		return $value;
	}

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

    public function getUid($value, array $def)
	{
		if($def['auto'] and is_null($value))
			return Str::superRandom($def['length'], $def['prefix'], $def['pool']);
		return $value;
	}

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
		if(!is_array($definition)) $definition = ['type' => $definition];

		$defaults = array_get(static::$defaultDefinitions, $definition['type'], ['type' => static::Mixed]);

		return array_merge($this->baseDefinition, $defaults, $definition);
	}

	protected function newDateObject($value = null)
	{
		if($value) return new DateTime($value);
		return new DateTime;
	}

	protected function newDateFromTimestamp($value)
	{
		$date = new DateTime;
		$date->setTimestamp($value);
		return $date;
	}

	protected function newDateFromFormat($value, $format)
	{
		return new DateTime($value);
	}
}