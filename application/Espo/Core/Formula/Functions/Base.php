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

namespace Espo\Core\Formula\Functions;

use Espo\Core\Formula\Exceptions\Error;
use Espo\ORM\Entity;
use Espo\Core\Formula\Processor;
use Espo\Core\Formula\Argument;

use stdClass;

/**
 * @deprecated Use Func interface instead.
 * @todo Remove in v11.0.
 */
abstract class Base
{
    /**
     * @var ?string
     */
    protected $name;

    /**
     * @var Processor
     */
    protected $processor;

    /**
     * @var ?Entity
     */
    private $entity;

    /**
     * @var ?\stdClass
     */
    private $variables;

    public function __construct(string $name, Processor $processor, ?Entity $entity = null, ?stdClass $variables = null)
    {
        $this->name = $name;
        $this->processor = $processor;
        $this->entity = $entity;
        $this->variables = $variables;
    }

    protected function getVariables(): stdClass
    {
        return $this->variables ?? (object) [];
    }

    /**
     * @throws Error
     */
    protected function getEntity() /** @phpstan-ignore-line */
    {
        if (!$this->entity) {
            throw new Error('Formula: Entity required but not passed.');
        }

        return $this->entity;
    }

    /**
     * @return mixed
     * @throws Error
     */
    public abstract function process(stdClass $item);

    /**
     * @param mixed $item
     * @return mixed
     * @throws Error
     */
    protected function evaluate($item)
    {
        $item = new Argument($item);

        return $this->processor->process($item);
    }

    /**
     * @return mixed[]
     * @throws Error
     */
    protected function fetchArguments(stdClass $item): array
    {
        $args = $item->value ?? [];

        $eArgs = [];

        foreach ($args as $item) {
            $eArgs[] = $this->evaluate($item);
        }

        return $eArgs;
    }

    /**
     * @return mixed[]
     */
    protected function fetchRawArguments(stdClass $item): array
    {
        return $item->value ?? [];
    }
}
