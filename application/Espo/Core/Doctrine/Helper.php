<?php

namespace Espo\Core\Doctrine;

class Helper
{
	private $entityManager;

	private $schemaTool;

	private $disconnectedClassMetadataFactory;

	private $entityGenerator;


	public function __construct(\Espo\Core\EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;

		$this->schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->getEntityManager());
        $this->entityGenerator = new \Doctrine\ORM\Tools\EntityGenerator();

		$this->disconnectedClassMetadataFactory = new \Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
        $this->disconnectedClassMetadataFactory->setEntityManager($this->getEntityManager());   // $em is EntityManager instance
	}

	protected function getEntityManager()
	{
		return $this->entityManager;
	}

	protected function getSchemaTool()
	{
		return $this->schemaTool;
	}

	protected function getDisconnectedClassMetadataFactory()
	{
		return $this->disconnectedClassMetadataFactory;
	}

	protected function getEntityGenerator()
	{
		return $this->entityGenerator;
	}


}