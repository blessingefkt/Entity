<?php namespace Iyoworks\Entity;

use DateTime;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;

class Transformer {
    /**
     * @var array
     */
    protected static $extraSmashers = [];
    /**
     * @var array
     */
    protected static $extraBuilders = [];
    /**
     * Definitions for transforming data types
     * @var array
     */
    protected $rules = [];
    /**
     * Init
     * @param array $rules
     */
    public function __construct($rules = [])
    {
        $this->rules = $rules;
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function smash($key, $value)
    {
        list($type, $ruleArgs) = $this->parseType($key);
        $method = 'smash'.studly_case($type);
        if(method_exists(get_called_class(), $method))
            return $this->$method($value, $ruleArgs);
        return $this->smashExtendedType($type, $value, $ruleArgs);
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function build($key, $value)
    {
        list($type, $ruleArgs) = $this->parseType($key);
        $method = 'build'.studly_case($type);
        if(method_exists(get_called_class(), $method))
            return $this->$method($value, $ruleArgs);
        return $this->buildExtendedType($type, $value, $ruleArgs);
    }

    /**
     * Checks if an attribute is a date type
     * @param  string
     * @return boolean
     */
    public function isDateType($type)
    {
        return in_array($type, ['datetime', 'time', 'date']);
    }

    /**
     * @return array
     */
    public function smashData($data)
    {
        $out = [];
        foreach ($data as $key => $value) {
            $out[$key] = $this->smash($key, $value);
        }
        return $out;
    }

    /**
     * @param $value
     * @param Fluent $ruleArgs
     * @return int
     */
    public function buildInteger($value, $ruleArgs)
    {
        return (int) $value;
    }

    /**
     * @param $value
     * @param Fluent $ruleArgs
     * @return bool
     */
    public function buildBoolean($value, $ruleArgs)
    {
        return (bool) $value;
    }

    /**
     * @param $value
     * @param Fluent $ruleArgs
     * @return float
     */
    public function buildDouble($value, $ruleArgs)
    {
        return (double) $value;
    }

    /**
     * @param $value
     * @param Fluent $ruleArgs
     * @return float
     */
    public function buildFloat($value, $ruleArgs)
    {
        return (float) $value;
    }

    /**
     * @param $value
     * @param Fluent $ruleArgs
     * @return mixed
     */
    public function smashHandle($value, $ruleArgs)
    {
        return Str::camel($value);
    }

    /**
     * @param $value
     * @param Fluent $ruleArgs
     * @return string
     */
    public function smashJson($value, $ruleArgs)
    {
        if($value instanceof \Illuminate\Support\Contracts\JsonableInterface)
            $output = $value->toJson();
        elseif(!is_string($value) or $ruleArgs->get('force'))
            $output = json_encode($value ?: []);
        else
            $output = $value;
        return $output;
    }

    /**
     * @param $value
     * @param Fluent $ruleArgs
     * @return array|mixed
     */
    public function buildJson($value, $ruleArgs)
    {
        if (empty($value) or $value == 'null')
            $output = [];
        else
            $output = json_decode($value, 1);

        return $output;
    }

    /**
     * @param $value
     * @param Fluent $ruleArgs
     * @return int
     */
    public function smashObject($value, $ruleArgs)
    {
        $class = $ruleArgs->get('class');
        if (is_subclass_of($class, '\Illuminate\Support\Contracts\JsonableInterface'))
            return $value->toJson();
        if (is_subclass_of($class, '\Illuminate\Support\Contracts\ArrayableInterface'))
            return $this->smashJson($value->toArray(), $ruleArgs);
        return  $this->smashSerial($value, $ruleArgs);
    }

    /**
     * @param $value
     * @param Fluent $ruleArgs
     * @return string
     */
    public function smashSerial($value, $ruleArgs)
    {
        return serialize($value);
    }

    /**
     * @param $value
     * @param Fluent $ruleArgs
     * @return mixed
     */
    public function buildSerial($value, $ruleArgs)
    {
        return unserialize($value);
    }

    /**
     * @param $value
     * @param Fluent $ruleArgs
     * @return mixed
     */
    public function smashSlug($value, $ruleArgs)
    {
        return \Str::slug($value);
    }

    /**
     * @param $value
     * @param Fluent $ruleArgs
     * @return string
     */
    public function smashString($value, $ruleArgs)
    {
        return (string) $value;
    }

    /**
     * @param $value
     * @param Fluent $ruleArgs
     * @return string
     */
    public function buildString($value, $ruleArgs)
    {
        return (string)  $value;
    }

    /**
     * @param $value
     * @param Fluent $ruleArgs
     * @return string
     */
    public function smashTimestamp($value, $ruleArgs)
    {
        $format = $ruleArgs->get('format', 'Y-m-d H:i:s');
        if(is_string($value))
        {
            $date = $this->newDateObject($value);
            return $date->format($format);
        }
        return $value->format($format);
    }

    /**
     * @param $value
     * @param Fluent $ruleArgs
     * @return DateTime
     */
    public function buildTimestamp($value, $ruleArgs)
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

        return $this->newDateFromFormat($value, $ruleArgs->get('format'));
    }

    /**
     * @param string $key
     * @param Fluent $ruleArgs
     * @return string
     */
    public function parseType($key)
    {
        $ruleArgs = new Fluent();
        $type = array_get($this->rules, $key, '_undefined');
        if (Str::contains($type, ',')) {
            $typeArgs = explode(',', $type);
            $type = trim(array_shift($typeArgs));
            foreach ($typeArgs as $arg) {
                list($key, $value) = explode(":", $arg);
                $ruleArgs->$key = trim($value);
            }
        }
        if (class_exists($type)) {
            $ruleArgs->class = $type;
            $type = 'object';
        }
        return [$type, $ruleArgs];
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
        if ($format) return DateTime::createFromFormat($format, $value);
        return new DateTime($value);
    }

    public static function addType($type, $smasher, $builder = null)
    {
        static::$extraSmashers[$type] = $smasher;
        if($builder) static::$extraBuilders[$type] =  $builder;
    }

    public function buildExtendedType($type, $value, $args)
    {
        if(isset(static::$extraBuilders[$type]))
            return call_user_func(static::$extraBuilders[$type], $value, $args);
        return $value;
    }

    public function smashExtendedType($type, $value, $args)
    {
        if(isset(static::$extraSmashers[$type]))
            return call_user_func(static::$extraSmashers[$type], $value, $args);
        return $value;
    }
}