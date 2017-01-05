<?php

namespace Espo\Core\Formula;

use \Espo\Core\Exceptions\Error;

class Evaluator
{
    private $functionFactory;

    private $formula;

    private $parser;

    private $parsedHash;

    public function __construct($container = null, array $functionClassNameMap = array(), array $parsedHash = array())
    {
        $this->functionFactory = new \Espo\Core\Formula\FunctionFactory($container, $functionClassNameMap);
        $this->formula = new \Espo\Core\Formula\Formula($this->functionFactory);
        $this->parser = new \Espo\Core\Formula\Parser();
        $this->parsedHash = array();
    }

    public function process($expression, $entity = null, $variables = null)
    {
        if (!array_key_exists($expression, $this->parsedHash)) {
            $item = $this->parser->parse($expression);
            $this->parsedHash[$expression] = $item;
        } else {
            $item = $this->parsedHash[$expression];
        }

        if (!$item || !($item instanceof \StdClass)) {
            throw new Error();
        }
        return $this->formula->process($item, $entity, $variables);
    }
}