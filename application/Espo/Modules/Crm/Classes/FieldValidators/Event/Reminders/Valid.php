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

namespace Espo\Modules\Crm\Classes\FieldValidators\Event\Reminders;

use Espo\Core\FieldValidation\Validator;
use Espo\Core\FieldValidation\Validator\Data;
use Espo\Core\FieldValidation\Validator\Failure;
use Espo\Modules\Crm\Entities\Reminder;
use Espo\ORM\Defs;
use Espo\ORM\Entity;
use stdClass;

/**
 * @implements Validator<Entity>
 */
class Valid implements Validator
{
    public function __construct(
        private Defs $ormDefs
    ) {}

    public function validate(Entity $entity, string $field, Data $data): ?Failure
    {
        /** @var ?mixed[] $list */
        $list = $entity->get($field);

        if ($list === null) {
            return null;
        }

        $typeList = $this->ormDefs
            ->getEntity(Reminder::ENTITY_TYPE)
            ->getField('type')
            ->getParam('options') ?? [];

        foreach ($list as $item) {
            if (!$item instanceof stdClass) {
                return Failure::create();
            }

            $seconds = $item->seconds ?? null;
            $type = $item->type ?? null;

            if (!is_int($seconds)) {
                return Failure::create();
            }

            if ($seconds < 0) {
                return Failure::create();
            }

            if (!in_array($type, $typeList)) {
                return Failure::create();
            }
        }

        return null;
    }
}
