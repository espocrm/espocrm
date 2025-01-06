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

namespace Espo\Tools\FieldManager\Hooks;

use Espo\Core\Di;
use Espo\Core\Exceptions\Error;
use Espo\Core\ORM\Type\FieldType;

class AutoincrementType implements Di\MetadataAware
{
    use Di\MetadataSetter;

    /**
     * @param array<string, mixed> $defs
     * @param array<string, mixed> $options
     * @throws Error
     */
    public function beforeSave(string $scope, string $name, $defs, $options): void
    {
        if (!isset($options['isNew']) || !$options['isNew']) {
            return;
        }

        $fields = $this->metadata->get(['entityDefs', $scope, 'fields']);

        foreach ($fields as $fieldDefs) {
            if ($fieldDefs['type'] == FieldType::AUTOINCREMENT) {
                throw new Error('The entity can have only one Auto-increment field.');
            }
        }
    }
}
