<?php namespace Iyoworks\Entity\Database;

interface EntitableInterface {

	public function buildEntity(array $attributes, array $relations);

	public function usesModel();

	public function newEntityCollection($entities);
} 