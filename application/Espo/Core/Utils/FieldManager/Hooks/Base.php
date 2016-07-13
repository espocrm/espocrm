<?php

namespace Espo\Core\Utils\FieldManager\Hooks;

abstract class Base
{
    protected $dependencyList = [
        'entityManager',
        'config',
        'metadata',
    ];

    protected $injections = array();

    public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
    }

    public function getDependencyList()
    {
        return $this->dependencyList;
    }

    protected function addDependencyList(array $list)
    {
        foreach ($list as $item) {
            $this->addDependency($item);
        }
    }

    protected function addDependency($name)
    {
        $this->dependencies[] = $name;
    }

    protected function getInjection($name)
    {
        return $this->injections[$name];
    }

    public function inject($name, $object)
    {
        $this->injections[$name] = $object;
    }

    protected function getMetadata()
    {
        return $this->getInjection('metadata');
    }

    protected function getConfig()
    {
        return $this->getInjection('config');
    }

    protected function getEntityManager()
    {
        return $this->getInjection('entityManager');
    }
}