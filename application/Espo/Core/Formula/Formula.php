<?php

namespace Espo\Core\Formula;

use \Espo\ORM\Entity;

class Formula
{
    private $functionFactory;

    public function __construct(FunctionFactory $functionFactory)
    {
        $this->functionFactory = $functionFactory;
    }

    public function process(\StdClass $item, $entity = null, $variables = null)
    {
        if (is_null($variables)) {
            $variables = (object)[];
        }
        return $this->functionFactory->create($item, $entity, $variables)->process($item);
    }
}