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

namespace Espo\Tools\MassUpdate;

use Espo\Core\ORM\Defs\AttributeParam;
use Espo\ORM\Entity;
use Espo\ORM\Defs as OrmDefs;

use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Utils\ObjectUtil;

use stdClass;

class ValueMapPreparator
{
    private OrmDefs $ormDefs;

    public function __construct(OrmDefs $ormDefs)
    {
        $this->ormDefs = $ormDefs;
    }

    public function prepare(Entity $entity, Data $data): stdClass
    {
        $map = (object) [];

        $this->loadAdditionalFields($entity, $data);

        foreach ($data->getAttributeList() as $attribute) {
            if ($data->getAction($attribute) === Action::UPDATE) {
                $map->$attribute = $data->getValue($attribute);

                continue;
            }

            if ($data->getValue($attribute) === null) {
                continue;
            }

            if (!$entity->has($attribute)) {
                continue;
            }

            if ($data->getAction($attribute) === Action::ADD) {
                $map->$attribute = $this->prepareItemAdd($entity->get($attribute), $data->getValue($attribute));

                continue;
            }

            if ($data->getAction($attribute) === Action::REMOVE) {
                $map->$attribute = $this->prepareItemRemove($entity->get($attribute), $data->getValue($attribute));

                continue;
            }
        }

        return $map;
    }

    private function loadAdditionalFields(Entity $entity, Data $data): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        foreach ($data->getAttributeList() as $attribute) {
            if ($entity->has($attribute)) {
                continue;
            }

            if (
                $entity->getAttributeParam($attribute, AttributeParam::IS_LINK_MULTIPLE_ID_LIST) &&
                $entity->getAttributeParam($attribute, 'relation')
            ) {
                $field = $entity->getAttributeParam($attribute, 'relation');

                $entity->loadLinkMultipleField($field);
            }
        }
    }

    /**
     * @param mixed $set
     * @param mixed $ch
     * @return mixed
     */
    private function prepareItemAdd($set, $ch)
    {
        if ($set === null && $ch === null) {
            return null;
        }

        if (is_array($set) || is_array($ch)) {
            $set = $set ?? [];
            $ch = $ch ?? [];

            if (!is_array($set) || !is_array($ch)) {
                return $set;
            }

            return $this->prepareItemAddArray($set, $ch);
        }

        if ($set instanceof stdClass || $ch instanceof stdClass) {
            $set = $set ?? (object) [];
            $ch = $ch ?? (object) [];

            if (!$set instanceof stdClass || !$ch instanceof stdClass) {
                return $set;
            }

            return $this->prepareItemAddObject($set, $ch);
        }

        return $set;
    }

    /**
     * @param mixed[] $set
     * @param mixed[] $ch
     * @return mixed[]
     */
    private function prepareItemAddArray(array $set, array $ch): array
    {
        if ($ch === []) {
            return $set;
        }

        $result = $set;

        foreach ($ch as $value) {
            if (in_array($value, $result)) {
                continue;
            }

            $result[] = $value;
        }

        return $result;
    }

    private function prepareItemAddObject(stdClass $set, stdClass $ch): stdClass
    {
        $result = ObjectUtil::clone($set);

        foreach (get_object_vars($ch) as $key => $value) {
            $result->$key = $value;
        }

        return $result;
    }

    /**
     * @param mixed $set
     * @param mixed $ch
     * @return mixed
     */
    private function prepareItemRemove($set, $ch)
    {
        if ($set === null && $ch === null) {
            return null;
        }

        if (is_array($set) || is_array($ch)) {
            $set = $set ?? [];
            $ch = $ch ?? [];

            if (!is_array($set) || !is_array($ch)) {
                return $set;
            }

            return $this->prepareItemRemoveArray($set, $ch);
        }

        if ($set instanceof stdClass || $ch instanceof stdClass) {
            $set = $set ?? (object) [];
            $ch = $ch ?? (object) [];

            if (!$set instanceof stdClass || !$ch instanceof stdClass) {
                return $set;
            }

            return $this->prepareItemRemoveObject($set, $ch);
        }

        return $set;
    }

    /**
     * @param mixed[] $set
     * @param mixed[] $ch
     * @return mixed[]
     */
    private function prepareItemRemoveArray(array $set, array $ch): array
    {
        if ($ch === []) {
            return $set;
        }

        $result = $set;

        foreach ($result as $i => $value) {
            if (!in_array($value, $ch)) {
                continue;
            }

            unset($result[$i]);
        }

        return array_values($result);
    }

    private function prepareItemRemoveObject(stdClass $set, stdClass $ch): stdClass
    {
        $result = ObjectUtil::clone($set);

        foreach (array_keys(get_object_vars($ch)) as $key) {
            unset($result->$key);
        }

        return $result;
    }
}
