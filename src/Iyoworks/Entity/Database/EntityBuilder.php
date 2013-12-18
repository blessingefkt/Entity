<?php namespace Iyoworks\Entity\Database;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class EntityBuilder extends EloquentBuilder {
	protected $useModel = false;

    /**
	 * Execute the query as a "select" statement.
	 *
	 * @param  array  $columns
	 * @return \Illuminate\Database\Eloquent\Collection|static[]
	 */
	public function get($columns = array('*'))
	{
		$models = parent::get($columns);

		if ($this->model instanceof EntitableInterface && !$this->useModel)
		{
			$entities = $this->getEntities($models);
			return $this->model->newEntityCollection($entities);
		}

		return $models;
	}

    /**
     * Set a model instance for the model being queried.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param bool $useModel
     * @return $this
     */
	public function setModel(\Illuminate\Database\Eloquent\Model $model, $useModel = false)
	{
		parent::setModel($model);
		$this->useModel = $useModel;
		return $this;
	}

	/**
	 * @param $models
	 * @return array
	 */
	protected function getEntities($models)
	{
		$entities = [];
		foreach($models as $k => $model)
		{
			$entities[$k] = $this->model->buildEntity($model->getAttributes(), $model->getRelations());
		}
		return $entities;
	}
}
