<?php

namespace Espo\Core\Formula;

use \Espo\Core\Exceptions\Error;

class Manager
{
    public function __construct(\Espo\Core\Container $container, \Espo\Core\Utils\Metadata $metadata)
    {
        $functionClassNameMap = $metadata->get(['app', 'formula', 'functionClassNameMap'], array());

        $this->evaluator = new \Espo\Core\Formula\Evaluator($container, $functionClassNameMap);
    }

    public function run($script, $entity = null, $variables = null)
    {
        return $this->evaluator->process($script, $entity, $variables);
    }
}