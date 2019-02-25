<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Repositories;

use Espo\ORM\Entity;

use \Espo\Core\Exceptions\Error;

class ArrayValue extends \Espo\Core\ORM\Repositories\RDB
{
    protected $hooksDisabled = true;

    protected $processFieldsAfterSaveDisabled = true;

    protected $processFieldsBeforeSaveDisabled = true;

    protected $processFieldsAfterRemoveDisabled = true;

    public function storeEntityAttribute(Entity $entity, $attribute, $populateMode = false)
    {
        if (!$entity->getAttributeType($attribute) === Entity::JSON_ARRAY) {
            throw new Error("ArrayValue: Can't store non array attribute.");
        }
        if ($entity->getAttributeType('notStorable')) return;
        if (!$entity->getAttributeParam($attribute, 'storeArrayValues')) return;
        if (!$entity->has($attribute)) return;

        $valueList = $entity->get($attribute);

        if (is_null($valueList)) {
            $valueList = [];
        }

        if (!is_array($valueList)) throw new Error("ArrayValue: Bad value passed to JSON_ARRAY attribute {$attribute}.");

        $valueList = array_unique($valueList);

        $toSkipValueList = [];

        if (!$entity->isNew() && !$populateMode) {
            $existingList = $this->where([
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->id,
                'attribute' => $attribute
            ])->find();

            foreach ($existingList as $existing) {
                if (!in_array($existing->get('value'), $valueList)) {
                    $this->deleteFromDb($existing->id);
                } else {
                    $toSkipValueList[] = $existing->get('value');
                }
            }
        }

        foreach ($valueList as $value) {
            if (in_array($value, $toSkipValueList)) continue;
            if (!is_string($value)) continue;

            $arrayValue = $this->get();
            $arrayValue->set([
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->id,
                'attribute' => $attribute,
                'value' => $value
            ]);
            $this->save($arrayValue);
        }
    }

    public function deleteEntityAttribute(Entity $entity, $attribute)
    {
        if (!$entity->id) {
            throw new Error("ArrayValue: Can't delete {$attribute} w/o id given.");
        }
        $list = $this->select(['id'])->where([
            'entityType' => $entity->getEntityType(),
            'entityId' => $entity->id,
            'attribute' => $attribute
        ])->find();

        foreach ($list as $arrayValue) {
            $this->deleteFromDb($arrayValue->id);
        }
    }
}
