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

namespace Espo\Tools\LinkManager;

use Espo\Tools\LinkManager\ParamsBuilder;

/**
 * Immutable.
 */
class Params
{
    private string $type;
    private string $entityType;
    private string $link;
    private string $foreignLink;
    private ?string $foreignEntityType;
    private ?string $name;

    public function __construct(
        string $type,
        string $entityType,
        string $link,
        ?string $foreignEntityType,
        string $foreignLink,
        ?string $name
    ) {
        $this->type = $type;
        $this->entityType = $entityType;
        $this->link = $link;
        $this->foreignEntityType = $foreignEntityType;
        $this->foreignLink = $foreignLink;
        $this->name = $name;
    }

    public static function createBuilder(): ParamsBuilder
    {
        return new ParamsBuilder();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getForeignLink(): string
    {
        return $this->foreignLink;
    }

    public function getForeignEntityType(): ?string
    {
        return $this->foreignEntityType;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
