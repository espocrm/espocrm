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

namespace Espo\Core\Record\Duplicator;

use Espo\ORM\Entity;
use Espo\ORM\Defs;
use Espo\ORM\Defs\FieldDefs;
use Espo\Core\Utils\FieldUtil;

use stdClass;

/**
 * Duplicates an entity.
 */
class EntityDuplicator
{
    /**
     * @var Defs
     */
    private $defs;

    /**
     * @var FieldDuplicatorFactory
     */
    private $fieldDuplicatorFactory;

    /**
     * @var FieldUtil
     */
    private $fieldUtil;

    public function __construct(Defs $defs, FieldDuplicatorFactory $fieldDuplicatorFactory, FieldUtil $fieldUtil)
    {
        $this->defs = $defs;
        $this->fieldDuplicatorFactory = $fieldDuplicatorFactory;
        $this->fieldUtil = $fieldUtil;
    }

    public function duplicate(Entity $entity): stdClass
    {
        $entityType = $entity->getEntityType();

        $valueMap = $entity->getValueMap();

        unset($valueMap->id);

        $entityDefs = $this->defs->getEntity($entityType);

        foreach ($entityDefs->getFieldList() as $fieldDefs) {
            $this->processField($entity, $fieldDefs, $valueMap);
        }

        return $valueMap;
    }

    private function processField(Entity $entity, FieldDefs $fieldDefs, stdClass $valueMap): void
    {
        $entityType = $entity->getEntityType();

        $field = $fieldDefs->getName();

        if ($fieldDefs->getParam('duplicateIgnore')) {
            $attributeList = $this->fieldUtil->getAttributeList($entityType, $field);

            foreach ($attributeList as $attribute) {
                unset($valueMap->$attribute);
            }

            return;
        }

        if (!$this->fieldDuplicatorFactory->has($entityType, $field)) {
            return;
        }

        $fieldDuplicator = $this->fieldDuplicatorFactory->create($entityType, $field);

        $fieldValueMap = $fieldDuplicator->duplicate($entity, $field);

        foreach (get_object_vars($fieldValueMap) as $attribute => $value) {
            $valueMap->$attribute = $value;
        }
    }
}
