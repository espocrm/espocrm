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

namespace Espo\Tools\Export\Processor;

use RuntimeException;

/**
 * Immutable.
 */
class Params
{
    private string $fileName;
    /** @var string[] */
    private array $attributeList;
    /** @var ?string[] */
    private ?array $fieldList = null;
    private ?string $name = null;
    private ?string $entityType = null;
    /** @var array<string, mixed> */
    private array $params = [];

    /**
     * @param string[] $attributeList
     * @param ?string[] $fieldList
     */
    public function __construct(string $fileName, array $attributeList, ?array $fieldList)
    {
        $this->fileName = $fileName;
        $this->attributeList = $attributeList;
        $this->fieldList = $fieldList;
    }

    public function withEntityType(string $entityType): self
    {
        $obj = clone $this;
        $obj->entityType = $entityType;

        return $obj;
    }

    public function withName(?string $name): self
    {
        $obj = clone $this;
        $obj->name = $name;

        return $obj;
    }

    /**
     * @param ?string[] $fieldList
     */
    public function withFieldList(?array $fieldList): self
    {
        $obj = clone $this;
        $obj->fieldList = $fieldList;

        return $obj;
    }

    /**
     * @param string[] $attributeList
     */
    public function withAttributeList(array $attributeList): self
    {
        $obj = clone $this;
        $obj->attributeList = $attributeList;

        return $obj;
    }

    public function withParam(string $name, mixed $value): self
    {
        $obj = clone $this;
        $obj->params[$name] = $value;

        return $obj;
    }

    /**
     * An export file name.
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * Attributes to export.
     *
     * @return string[]
     */
    public function getAttributeList(): array
    {
        return $this->attributeList;
    }

    /**
     * Fields to export.
     *
     * @return ?string[]
     */
    public function getFieldList(): ?array
    {
        return $this->fieldList;
    }

    /**
     * An export name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * An entity type.
     */
    public function getEntityType(): string
    {
        if ($this->entityType === null) {
            throw new RuntimeException("No entity-type.");
        }

        return $this->entityType;
    }

    /**
     * Get a parameter value.
     */
    public function getParam(string $name): mixed
    {
        return $this->params[$name] ?? null;
    }
}
