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

namespace Espo\Classes\FieldValidators\Email\Addresses;

use Espo\Core\FieldValidation\Validator;
use Espo\Core\FieldValidation\Validator\Data;
use Espo\Core\FieldValidation\Validator\Failure;
use Espo\Core\Utils\Config;
use Espo\Entities\Email;
use Espo\ORM\Entity;

use LogicException;

/**
 * @implements Validator<Email>
 */
class MaxCount implements Validator
{
    private const MAX_COUNT = 100;

    public function __construct(private Config $config) {}

    /**
     * @param Email $entity
     */
    public function validate(Entity $entity, string $field, Data $data): ?Failure
    {
        if ($field === 'to') {
            $addresses = $entity->getToAddressList();
        } else if ($field === 'cc') {
            $addresses = $entity->getCcAddressList();
        } else if ($field === 'bcc') {
            $addresses = $entity->getBccAddressList();
        } else {
            throw new LogicException();
        }

        $maxCount = $this->config->get('emailRecipientAddressMaxCount') ?? self::MAX_COUNT;

        if (count($addresses) > $maxCount) {
            return Failure::create();
        }

        return null;
    }
}
