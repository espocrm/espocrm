<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Classes\FieldValidators;

use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;
use Espo\Core\ORM\Entity as CoreEntity;

class LinkMultipleType
{
    private Metadata $metadata;

    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    public function checkRequired(Entity $entity, string $field): bool
    {
        if (!$entity instanceof CoreEntity) {
            return false;
        }

        /** @var string[] */
        $idList = $entity->getLinkMultipleIdList($field);

        return count($idList) > 0;
    }

    public function checkPattern(Entity $entity, string $field): bool
    {
        /** @var ?mixed[] */
        $idList = $entity->get($field . 'Ids');

        if ($idList === null || $idList === []) {
            return true;
        }

        $pattern = $this->metadata->get(['app', 'regExpPatterns', 'id', 'pattern']);

        if (!$pattern) {
            return true;
        }

        $preparedPattern = '/^' . $pattern . '$/';

        foreach ($idList as $id) {
            if (!is_string($id)) {
                return false;
            }

            if (!preg_match($preparedPattern, $id)) {
                return false;
            }
        }

        return true;
    }
}
