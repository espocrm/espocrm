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

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;

use stdClass;

/**
 * An access point for the formula functionality.
 */
class Manager
{
    private Evaluator $evaluator;

    public function __construct(InjectableFactory $injectableFactory, Metadata $metadata)
    {
        $functionClassNameMap = $metadata->get(['app', 'formula', 'functionClassNameMap'], []);

        $unsafeFunctionList = $this->getUnsafeFunctionList($metadata);

        $this->evaluator = new Evaluator($injectableFactory, $functionClassNameMap, $unsafeFunctionList);
    }

    /**
     * Executes a script and returns its result.
     *
     * @throws Exceptions\Error
     */
    public function run(string $script, ?Entity $entity = null, ?stdClass $variables = null) : mixed
    {
        return $this->evaluator->process($script, $entity, $variables);
    }

    /**
     * Executes a script in safe mode and returns its result.
     *
     * @throws Exceptions\Error
     * @since 8.3.0
     * @internal
     */
    public function runSafe(string $script, ?Entity $entity = null, ?stdClass $variables = null): mixed
    {
        return $this->evaluator->processSafe($script, $entity, $variables);
    }

    /**
     * @return string[]
     */
    private function getUnsafeFunctionList(Metadata $metadata): array
    {
        $unsafeFunctionList = [];

        foreach ($metadata->get("app.formula.functionList") ?? [] as $item) {
            if ($item['unsafe'] ?? false) {
                $unsafeFunctionList[] = $item['name'];
            }
        }
        return $unsafeFunctionList;
    }
}
