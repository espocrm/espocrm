<?php

namespace Espo\Core\Formula;

use \Espo\Core\Exceptions\Error;
use \Espo\ORM\Entity;

class FunctionFactory
{
    private $container;

    private $classNameMap;

    public function __construct($container, $classNameMap = null)
    {
        $this->container = $container;
        $this->attributeFetcher = new AttributeFetcher();
        $this->classNameMap = $classNameMap;
    }

    public function create(\StdClass $item, $entity, \StdClass $variables)
    {
        if (!isset($item->type)) {
            throw new Error('Missing type');
        }

        if (!is_string($item->type)) {
            throw new Error('Bad type');
        }

        $name = $item->type;

        if ($this->classNameMap && array_key_exists($name, $this->classNameMap)) {
            $className = $this->classNameMap[$name];
        } else {
            $arr = explode('\\', $name);
            foreach ($arr as $i => $part) {
                if ($i < count($arr) - 1) {
                    $part = $part . 'Group';
                }
                $arr[$i] = ucfirst($part);
            }
            $name = implode('\\', $arr);
            $className = '\\Espo\\Core\\Formula\\Functions\\' . $name . 'Type';
        }

        if (!class_exists($className)) {
            throw new Error('Class ' . $className . ' was not found.');
        }

        $object = new $className($this, $entity, $variables);

        $dependencyList = $object->getDependencyList();
        foreach ($dependencyList as $name) {
            if (!$this->container) {
                throw new Error('Container required but not passed.');
            }
            $object->inject($name, $this->container->get($name));
        }

        if (property_exists($className, 'hasAttributeFetcher')) {
            $object->setAttributeFetcher($this->attributeFetcher);
        }
        return $object;
    }
}