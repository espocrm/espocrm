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

namespace Espo\Core\Select\Order;

use Espo\Core\Select\SearchParams;

use InvalidArgumentException;

/**
 * Order parameters.
 *
 * Immutable.
 */
class Params
{
    private bool $forceDefault = false;
    private mixed $orderBy = null;
    /** @var SearchParams::ORDER_ASC|SearchParams::ORDER_DESC|null */
    private $order = null;
    private bool $applyPermissionCheck = false;

    private function __construct() {}

    /**
     * @param array{
     *     forceDefault?: bool,
     *     orderBy?: ?string,
     *     order?: SearchParams::ORDER_ASC|SearchParams::ORDER_DESC|null,
     *     applyPermissionCheck?: bool,
     * } $params
     */
    public static function fromAssoc(array $params): self
    {
        $object = new self();

        $object->forceDefault = $params['forceDefault'] ?? false;
        $object->orderBy = $params['orderBy'] ?? null;
        $object->order = $params['order'] ?? null;
        $object->applyPermissionCheck = $params['applyPermissionCheck'] ?? false;

        foreach ($params as $key => $value) {
            if (!property_exists($object, $key)) {
                throw new InvalidArgumentException("Unknown parameter '{$key}'.");
            }
        }

        if ($object->orderBy && !is_string($object->orderBy)) {
            throw new InvalidArgumentException("Bad orderBy.");
        }

        /** @var ?string $order */
        $order = $object->order;

        if (
            $order &&
            $order !== SearchParams::ORDER_ASC &&
            $order !== SearchParams::ORDER_DESC
        ) {
            throw new InvalidArgumentException("Bad order.");
        }

        return $object;
    }

    /**
     * Force default order.
     */
    public function forceDefault(): bool
    {
        return $this->forceDefault;
    }

    /**
     * An order-By field.
     */
    public function getOrderBy(): ?string
    {
        /** @var ?string */
        return $this->orderBy;
    }

    /**
     * An order direction.
     *
     * @return SearchParams::ORDER_ASC|SearchParams::ORDER_DESC|null
     */
    public function getOrder(): ?string
    {
        return $this->order;
    }

    /**
     * Apply permission check.
     */
    public function applyPermissionCheck(): bool
    {
        return $this->applyPermissionCheck;
    }
}
