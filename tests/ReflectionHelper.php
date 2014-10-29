<?php

namespace tests;

class ReflectionHelper
{

    private $object;

    private $reflection;

    public function __construct($object)
    {
        $this->object = $object;
        $this->reflection = new \ReflectionClass(get_class($this->object));
    }

    public function invokeMethod($methodName, array $parameters = array())
    {
        $method = $this->reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->object, $parameters);
    }

    public function setProperty($name, $value)
    {
        $property = $this->reflection->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($this->object, $value);
        return $this->getProperty($name);
    }

    public function getProperty($name)
    {
        $property = $this->reflection->getProperty($name);
        $property->setAccessible(true);
        return $property->getValue($this->object);
    }
}