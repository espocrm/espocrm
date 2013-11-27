<?php

namespace Espo\Core\Doctrine;

class Helper
{
	private $entityManager;

	public function __construct(\Espo\Core\EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	protected function getEntityManager()
	{
		return $this->entityManager;
	}


}