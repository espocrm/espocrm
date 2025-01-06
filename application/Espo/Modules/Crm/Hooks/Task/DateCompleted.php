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

namespace Espo\Modules\Crm\Hooks\Task;

use Espo\Core\Field\DateTime;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Modules\Crm\Entities\Task;
use Espo\ORM\Entity;

class DateCompleted
{
    private const FIELD_DATE_COMPLETED = 'dateCompleted';
    private const FIELD_STATUS = 'status';

    public function __construct() {}

    /**
     * @param Task $entity
     * @param array<string, mixed> $options
     */
    public function beforeSave(Entity $entity, array $options): void
    {
        if (!$entity->isAttributeChanged(self::FIELD_STATUS)) {
            return;
        }

        if (
            ($options[SaveOption::IMPORT] ?? false) &&
            $entity->get(self::FIELD_DATE_COMPLETED)
        ) {
            return;
        }

        if ($entity->getStatus() !== Task::STATUS_COMPLETED) {
            $entity->set(self::FIELD_DATE_COMPLETED, null);

            return;
        }

        $entity->setValueObject(self::FIELD_DATE_COMPLETED, DateTime::createNow());
    }
}
