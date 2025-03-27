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

use Espo\Core\Formula\Exceptions\BadArgumentType;
use Espo\Core\Formula\Exceptions\BadArgumentValue;
use Espo\Core\Formula\Exceptions\ExecutionException;
use Espo\Core\Formula\Exceptions\TooFewArguments;
use Espo\Core\Formula\Exceptions\UndefinedKey;
use Espo\Core\Formula\Functions\Base as DeprecatedBaseFunction;
use Espo\Core\Formula\Functions\BaseFunction;
use Espo\Core\Formula\Parser\Ast\Attribute;
use Espo\Core\Formula\Parser\Ast\Node;
use Espo\Core\Formula\Parser\Ast\Value;
use Espo\Core\Formula\Parser\Ast\Variable;
use Espo\Core\Formula\Exceptions\Error;
use Espo\Core\InjectableFactory;

use Espo\ORM\Entity;

use InvalidArgumentException;
use stdClass;

/**
 * An instance of Processor is created for every formula script.
 */
class Processor
{
    private FunctionFactory $functionFactory;
    private stdClass $variables;

    /**
     * @param ?array<string, class-string<BaseFunction|Func|DeprecatedBaseFunction>> $functionClassNameMap
     */
    public function __construct(
        InjectableFactory $injectableFactory,
        AttributeFetcher $attributeFetcher,
        ?array $functionClassNameMap = null,
        private ?Entity $entity = null,
        ?stdClass $variables = null
    ) {
        $this->functionFactory = new FunctionFactory(
            $this,
            $injectableFactory,
            $attributeFetcher,
            $functionClassNameMap
        );

        $this->variables = $variables ?? (object) [];
    }

    /**
     * Evaluates an argument or argument list.
     *
     * @return mixed A result of evaluation. An array if an argument list was passed.
     * @throws Error
     * @throws ExecutionException
     */
    public function process(Evaluatable $item): mixed
    {
        if ($item instanceof ArgumentList) {
            return $this->processList($item);
        }

        if (!$item instanceof Argument) {
            throw new InvalidArgumentException();
        }

        $function = $this->functionFactory->create($item->getType(), $this->entity, $this->variables);

        if ($function instanceof Func || $function instanceof FuncVariablesAware) {
            return $this->processFunc($item, $function);
        }

        if ($function instanceof DeprecatedBaseFunction) {
            return $function->process(self::dataToStdClass($item->getData()));
        }

        try {
            return $function->process($item->getArgumentList());
        } catch (UndefinedKey $e) {
            throw UndefinedKey::cloneWithLevelRisen($e);
        }
    }

    /**
     * @throws Error
     */
    private function dataToStdClass(Node|Value|Attribute|Variable|string|float|int|bool|null $data): stdClass
    {
        if ($data instanceof Node) {
            return (object) [
                'type' => $data->getType(),
                'value' => $data->getChildNodes(),
            ];
        }

        if ($data instanceof Value) {
            return (object) [
                'type' => 'value',
                'value' => $data->getValue(),
            ];
        }

        if ($data instanceof Attribute) {
            return (object) [
                'type' => 'attribute',
                'value' => $data->getName(),
            ];
        }

        if ($data instanceof Variable) {
            return (object) [
                'type' => 'variable',
                'value' => $data->getName(),
            ];
        }

        throw new Error("Can't convert argument to a raw object.");
    }

    /**
     * @return mixed[]
     * @throws Error
     * @throws ExecutionException
     */
    private function processList(ArgumentList $args): array
    {
        $list = [];

        foreach ($args as $item) {
            $list[] = $this->process($item);
        }

        return $list;
    }

    /**
     * @throws Error
     * @throws ExecutionException
     */
    private function processFunc(Argument $item, Func|FuncVariablesAware $function): mixed
    {
        $rawEvaluatedArguments = array_map(
            fn ($item) => $this->process($item),
            iterator_to_array($item->getArgumentList())
        );

        try {
            $evaluatedArguments = new EvaluatedArgumentList($rawEvaluatedArguments);

            if ($function instanceof FuncVariablesAware) {
                $variables = new Variables($this->variables ?? (object) []);

                return $function->process($evaluatedArguments, $variables);
            }

            return $function->process($evaluatedArguments);
        } catch (TooFewArguments|BadArgumentType|BadArgumentValue $e) {
            $message = sprintf('Function %s; %s', $item->getType(), $e->getLogMessage());

            throw new Error($message);
        }
    }
}
