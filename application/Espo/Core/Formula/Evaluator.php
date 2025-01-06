<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Formula;

use Espo\Core\Formula\Exceptions\Error;
use Espo\Core\Formula\Exceptions\ExecutionException;
use Espo\Core\Formula\Exceptions\SyntaxError;
use Espo\Core\Formula\Exceptions\UnsafeFunction;
use Espo\Core\Formula\Functions\Base as DeprecatedBaseFunction;
use Espo\Core\Formula\Functions\BaseFunction;
use Espo\Core\Formula\Parser\Ast\Attribute;
use Espo\Core\Formula\Parser\Ast\Node;
use Espo\Core\Formula\Parser\Ast\Value;
use Espo\Core\Formula\Parser\Ast\Variable;
use Espo\ORM\Entity;
use Espo\Core\InjectableFactory;

use LogicException;
use stdClass;

/**
 * Creates an instance of Processor and executes a script.
 *
 * @internal
 */
class Evaluator
{
    private Parser $parser;
    private AttributeFetcher $attributeFetcher;
    /** @var array<string, (Node|Value|Attribute|Variable)> */
    private $parsedHash;

    /**
     * @param array<string, class-string<BaseFunction|Func|DeprecatedBaseFunction>> $functionClassNameMap
     * @param string[] $unsafeFunctionList
     */
    public function __construct(
        private InjectableFactory $injectableFactory,
        private array $functionClassNameMap = [],
        private array $unsafeFunctionList = []
    ) {
        $this->attributeFetcher = $injectableFactory->create(AttributeFetcher::class);
        $this->parser = new Parser();
        $this->parsedHash = [];
    }

    /**
     * Process expression.
     *
     * @throws SyntaxError
     * @throws Error
     */
    public function process(string $expression, ?Entity $entity = null, ?stdClass $variables = null): mixed
    {
        return $this->processInternal($expression, $entity, $variables, false);
    }

    /**
     * Process expression in safe mode.
     *
     * @throws SyntaxError
     * @throws Error
     */
    public function processSafe(string $expression, ?Entity $entity = null, ?stdClass $variables = null): mixed
    {
        return $this->processInternal($expression, $entity, $variables, true);
    }

    /**
     * @throws SyntaxError
     * @throws Error
     */
    private function processInternal(
        string $expression,
        ?Entity $entity,
        ?stdClass $variables,
        bool $safeMode,
    ): mixed {

        $processor = new Processor(
            $this->injectableFactory,
            $this->attributeFetcher,
            $this->functionClassNameMap,
            $entity,
            $variables
        );

        $item = $this->getParsedExpression($expression);

        if ($safeMode) {
            $this->checkIsSafe($item->getData());
        }

        try {
            $result = $processor->process($item);
        } catch (ExecutionException $e) {
            throw new LogicException('Unexpected ExecutionException.', 0, $e);
        }

        $this->attributeFetcher->resetRuntimeCache();

        return $result;
    }

    /**
     * @throws SyntaxError
     */
    private function getParsedExpression(string $expression): Argument
    {
        if (!array_key_exists($expression, $this->parsedHash)) {
            $this->parsedHash[$expression] = $this->parser->parse($expression);
        }

        return new Argument($this->parsedHash[$expression]);
    }

    /**
     * @throws UnsafeFunction
     */
    private function checkIsSafe(mixed $data): void
    {
        if (!$data instanceof Node) {
            return;
        }

        $name = $data->getType();

        if (in_array($name, $this->unsafeFunctionList)) {
            throw new UnsafeFunction("$name is not safe.");
        }

        foreach ($data->getChildNodes() as $subData) {
            $this->checkIsSafe($subData);
        }
    }
}
