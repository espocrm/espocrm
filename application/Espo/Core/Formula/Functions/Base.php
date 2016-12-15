<?php

namespace Espo\Core\Formula\Functions;

use \Espo\Core\Interfaces\Injectable;

use \Espo\ORM\Entity;
use \Espo\Core\Exceptions\Error;

abstract class Base implements Injectable
{
    protected $dependencyList = [];

    protected $itemFactory;

    protected $injections = array();

    private $entity;

    private $variables;

    public function inject($name, $object)
    {
        $this->injections[$name] = $object;
    }

    protected function init()
    {
    }

    protected function getInjection($name)
    {
        return $this->injections[$name];
    }

    protected function addDependency($name)
    {
        $this->dependencyList[] = $name;
    }

    protected function addDependencyList(array $list)
    {
        foreach ($list as $item) {
            $this->addDependency($item);
        }
    }

    public function getDependencyList()
    {
        return $this->dependencyList;
    }

    protected function getVariables()
    {
        return $this->variables;
    }

    protected function getEntity()
    {
        if (!$this->entity) {
            throw new Error('Entity required but not passed.');
        }
        return $this->entity;
    }

    public function __construct($itemFactory, $entity = null, $variables = null)
    {
        $this->itemFactory = $itemFactory;
        $this->entity = $entity;
        $this->variables = $variables;
        $this->init();
    }

    protected function getFactory()
    {
        return $this->itemFactory;
    }

    protected function evaluate($item)
    {
        $function = $this->getFactory()->create($item, $this->entity, $this->variables);
        return $function->process($item);
    }

    public abstract function process(\StdClass $item);
}