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

namespace Espo\Core\Select\Where;

use Espo\Core\Select\Where\Item\Data;

/**
 * A where-item builder.
 */
class ItemBuilder
{
    private ?string $type = null;
    private ?string $attribute = null;
    /** @var mixed */
    private $value = null;
    private ?Data $data = null;

    public static function create(): self
    {
        return new self();
    }

    /**
     * Set a type.
     *
     * @param (Item\Type::*)|string $type
     * @return $this
     * @noinspection PhpDocSignatureInspection
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set a value.
     *
     * @param mixed $value
     */
    public function setValue($value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Set an attribute.
     */
    public function setAttribute(?string $attribute): self
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * Set data.
     */
    public function setData(?Data $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set nested where item list.
     *
     * @param Item[] $itemList
     * @return self
     */
    public function setItemList(array $itemList): self
    {
        $this->value = array_map(
            function (Item $item): array {
                return $item->getRaw();
            },
            $itemList
        );

        return $this;
    }

    public function build(): Item
    {
        return Item
            ::fromRaw([
                'type' => $this->type,
                'attribute' => $this->attribute,
                'value' => $this->value,
            ])
            ->withData($this->data);
    }
}
