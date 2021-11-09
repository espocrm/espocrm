<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Formula;

use Espo\ORM\Entity;
use Espo\Core\InjectableFactory;

use stdClass;

/**
 * Creates an instance of Processor and executes a script.
 */
class Evaluator
{
    private $functionClassNameMap;

    private $processor;

    private $parser;

    private $attributeFetcher;

    private $injectableFactory;

    private $parsedHash;

    public function __construct(InjectableFactory $injectableFactory, array $functionClassNameMap = [])
    {
        $this->attributeFetcher = new AttributeFetcher();

        $this->injectableFactory = $injectableFactory;
        $this->functionClassNameMap = $functionClassNameMap;

        $this->parser = new Parser();

        $this->parsedHash = [];
    }

    /**
     * @return mixed
     */
    public function process(string $expression, ?Entity $entity = null, ?stdClass $variables = null)
    {
        $this->processor = new Processor(
            $this->injectableFactory, $this->attributeFetcher, $this->functionClassNameMap, $entity, $variables
        );

        $item = $this->getParsedExpression($expression);

        $result = $this->processor->process($item);

        $this->attributeFetcher->resetRuntimeCache();

        return $result;
    }

    private function getParsedExpression(string $expression): Argument
    {
        if (!array_key_exists($expression, $this->parsedHash)) {
            $this->parsedHash[$expression] = $this->parser->parse($expression);
        }

        return new Argument($this->parsedHash[$expression]);
    }
}
