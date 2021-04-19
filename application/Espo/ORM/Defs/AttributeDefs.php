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

namespace Espo\ORM\Defs;

/**
 * Attribute definitions.
 */
class AttributeDefs
{
    private $data;

    private $name;

    private function __construct()
    {
    }

    public static function fromRaw(array $raw, string $name): self
    {
        $obj = new self();

        $obj->data = $raw;

        $obj->name = $name;

        return $obj;
    }

    /**
     * Get a name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get a type.
     */
    public function getType(): string
    {
        return $this->data['type'];
    }

    /**
     * Get a length.
     */
    public function getLength(): ?int
    {
        return $this->data['len'] ?? null;
    }

    /**
     * Whether is not-storable. Not-storable attributes are not stored in DB.
     */
    public function isNotStorable(): bool
    {
        return $this->data['notStorable'] ?? false;
    }

    /**
     * Whether is auto-increment.
     */
    public function isAutoincrement(): bool
    {
        return $this->data['autoincrement'] ?? false;
    }

    /**
     * Whether a parameter is set.
     */
    public function hasParam(string $name): bool
    {
        return array_key_exists($name, $this->data);
    }

    /*
     * Get a parameter value by a name.
     *
     * @return mixed
     */
    public function getParam(string $name)
    {
        return $this->data[$name] ?? null;
    }
}
