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

namespace Espo\ORM\QueryParams;

use RuntimeException;

/**
 * Select parameters.
 *
 * @todo Add validation and normalization (from ORM\DB\BaseQuery).
 */
class Select implements Query, Selecting
{
    use SelectingTrait;
    use BaseTrait;

    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';

    /**
     * Get an entity type.
     */
    public function getFrom() : ?string
    {
        return $this->params['from'] ?? null;
    }

    /**
     * Get select items.
     */
    public function getSelect() : array
    {
        return $this->params['select'] ?? [];
    }

    /**
     * Get order.
     */
    public function getOrder() : array
    {
        return $this->params['orderBy'] ?? [];
    }

    /**
     * Whether is distinct.
     */
    public function isDistinct() : bool
    {
        return $this->params['distinct'] ?? false;
    }

    /**
     * Get group by.
     */
    public function getGroupBy() : array
    {
        return $this->params['orderBy'] ?? [];
    }

    protected function validateRawParams(array $params)
    {
        $this->validateRawParamsSelecting($params);

        if (
            (
                !empty($params['joins']) ||
                !empty($params['leftJoins']) ||
                !empty($params['whereClause']) ||
                !empty($params['orderBy'])
            )
            &&
            empty($params['from']) && empty($params['fromQuery'])
        ) {
            throw new RuntimeException("Select params: Missing 'from'.");
        }
    }
}
