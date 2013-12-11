<?php namespace Iyoworks\Entity\Database;

class AttributeEntityBuilder extends  EntityBuilder {

	/**
	 * @param $models
	 * @return array
	 */
	protected function getEntities($models)
	{
		$entities = [];
		foreach($models as $k => $model)
		{
			$attributes = $this->getEntityAttributes($this->model->newEntity(), $model);
			$relations = $this->getEntityRelations($this->model->newEntity(), $model);
			$entities[$k] = $this->model->buildEntity($attributes, $relations);
		}
		return $entities;
	}

	/**
	 * @param \Iyoworks\Entity\BaseEntity     $entity
	 * @param \Iyoworks\Entity\Database\BaseModel $model
	 * @return \Iyoworks\Entity\BaseEntity
	 */
	protected function getEntityAttributes($entity, $model)
	{
        return array_intersect_key($model->getAttributes(), array_flip($entity->getNonEntityAttributes()));
	}

	/**
	 * @param \Iyoworks\Entity\BaseEntity     $entity
	 * @param \Iyoworks\Entity\Database\BaseModel $model
	 * @return array
	 */
	protected function getEntityRelations($entity, $model)
	{
		$relations = [];
		foreach ( $model->getRelations() as $rel => $relatedModel )
		{
			if ($entity->isEntity($rel)) $relations[$rel] = $relatedModel->getAttributes();
		}
		return $relations;
	}
}
