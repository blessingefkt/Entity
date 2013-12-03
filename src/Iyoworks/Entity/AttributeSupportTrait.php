<?php namespace Iyoworks\Entity;

trait AttributeSupportTrait {

    /**
     * Get attribute definitions
     * @return array
     */
    abstract public function getAttributeDefinitions();

    /**
     * Determine if an attribute's type matches the given type
     * @param  string $key
     * @param  string $type
     * @return bool
     */
    public function attributeTypeMatches($key, $type)
    {
        $atype = $this->getAttributeType($key);
        return in_array($atype, (array) $type);
    }

    /**
     * Checks if an attribute has a definition
     * @param  string
     * @return boolean
     */
    public function isDefinedAttribute($key)
    {
        return array_key_exists($key, $this->getAttributeDefinitions());
    }

    /**
     * Get an attribute's definition
     * @param string $key
     * @return array
     */
    public function getAttributeDefinition($key)
    {
        return array_get($this->getAttributeDefinitions(), $key, ['type' => Attribute::Mixed]);
    }

    /**
     * Get the attribute type
     * @param $key
     * @return string
     */
    public function getAttributeType($key)
    {
        $definition = $this->getAttributeDefinition($key);
        return array_get($definition, 'type', Attribute::Mixed);
    }

    /**
     * Determine if attribute is guarded
     * @param $key
     * @return bool|null
     */
    public function isGuardedAttribute($key)
    {
        $definition = $this->getAttributeDefinition($key);
        return array_get($definition, 'guarded', null);
    }

    /**
     * Determine if all attributes are guarded
     * @return bool
     */
    public function allAttributesGuarded()
    {
        foreach ($this->getAttributeDefinitions() as $def)
        {
            if (!$def['guarded']) return false;
        }
        return true;
    }

    /**
     * Determine if attribute is visible
     * @param $key
     * @return bool|null
     */
    public function isVisibleAttribute($key)
    {
        $definition = $this->getAttributeDefinition($key);
        return array_get($definition, 'visible', null);
    }

    /**
     * Checks if an attribute is a date type
     * @param  string
     * @return boolean
     */
    public function isDateType($key)
    {
        return Attribute::isDateType($this->getAttributeType($key));
    }
} 