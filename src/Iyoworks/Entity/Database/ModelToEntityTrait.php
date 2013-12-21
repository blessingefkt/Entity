<?php namespace Iyoworks\Entity\Database;

use Illuminate\Database\Eloquent\Collection;

/**
 * Class ModelToEntityTrait
 * @package Iyoworks\Entity\Database
 */
trait ModelToEntityTrait {
    /**
     * @var bool
     */
    protected $useModel = false;

    /**
     * @return mixed
     */
    abstract public function newEntity();

    /**
     * @param array $attributes
     * @param array $relations
     * @return mixed
     */
    public function buildEntity(array $attributes, array $relations)
    {
        $entity = $this->newEntity()->buildInstance($attributes);
        foreach ($relations as $key => $relation)
            $entity->setAttribute($key, $relation);
        $entity->syncOriginal(true);
        return $entity;
    }

    /**
     * @param $entities
     * @return Collection
     */
    public function newEntityCollection($entities)
    {
        return $this->newCollection($entities);
    }

    /**
     * Get a new query builder for the model's table.
     *
     * @param  bool  $excludeDeleted
     * @return \Iyoworks\Entity\Database\EntityBuilder|static
     */
    public function newQuery($excludeDeleted = true)
    {
        $builder = $this->newEntityBuilder($this->newBaseQueryBuilder());

        // Once we have the query builders, we will set the model instances so the
        // builder can easily access any information it may need from the model
        // while it is constructing and executing various queries against it.
        $builder->setModel($this, $this->useModel)->with($this->with);

        if ($excludeDeleted && $this->softDelete)
        {
            $builder->whereNull($this->getQualifiedDeletedAtColumn());
        }

        return $builder;
    }

    /**
     * @param $baseBuilder
     * @return EntityBuilder
     */
    public function newEntityBuilder($baseBuilder)
    {
        return new EntityBuilder($baseBuilder);
    }

    /**
     * @return bool
     */
    public function usesModel()
    {
        return $this->useModel;
    }

    /**
     * @return $this
     */
    public function useModel()
    {
        $this->useModel = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function useEntity()
    {
        $this->useModel = false;
        return $this;
    }

} 