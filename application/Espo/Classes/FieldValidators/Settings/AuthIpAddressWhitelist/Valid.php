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

namespace Espo\Classes\FieldValidators\Settings\AuthIpAddressWhitelist;

use Espo\Core\FieldValidation\Validator;
use Espo\Core\FieldValidation\Validator\Data;
use Espo\Core\FieldValidation\Validator\Failure;
use Espo\ORM\Entity;

/**
 * @implements Validator<Entity>
 */
class Valid implements Validator
{
    public function validate(Entity $entity, string $field, Data $data): ?Failure
    {
        $list = $entity->get($field);

        if (!is_array($list)) {
            return null;
        }

        foreach ($list as $item) {
            if (!is_string($item)) {
                continue;
            }

            if (!$this->isValid($item)) {
                return Failure::create();
            }
        }

        return null;
    }

    private function isValid(string $item): bool
    {
        $address = $item;

        if (count(explode('/', $item)) > 1) {
            [$address, $mask] = explode('/', $item, 2);

            if (!is_numeric($mask)) {
                return false;
            }

            $mask = (int) $mask;

            if ($mask < 0 || $mask > 128) {
                return false;
            }
        }

        if (
            filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false &&
            filter_var($address, FILTER_VALIDATE_IP) === false
        ) {
            return false;
        }

        return true;
    }
}
