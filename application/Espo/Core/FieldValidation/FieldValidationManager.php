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

namespace Espo\Core\FieldValidation;

use Espo\ORM\Entity;

use Espo\Core\{
    Utils\Metadata,
    Utils\FieldUtil,
};

use StdClass;

class FieldValidationManager
{
    private $checkerCache = [];

    private $metadata;

    private $fieldUtil;

    private $factory;

    public function __construct(Metadata $metadata, FieldUtil $fieldUtil, ValidatorFactory $factory)
    {
        $this->metadata = $metadata;
        $this->fieldUtil = $fieldUtil;
        $this->factory = $factory;
    }

    public function check(Entity $entity, string $field, string $type, ?StdClass $data = null) : bool
    {
        if (!$data) {
            $data = $data ?? (object) [];
        }

        $entityType = $entity->getEntityType();

        $fieldType = $this->fieldUtil->getEntityTypeFieldParam($entityType, $field, 'type');
        $validationValue = $this->fieldUtil->getEntityTypeFieldParam($entityType, $field, $type);

        $mandatoryValidationList = $this->metadata->get(['fields', $fieldType, 'mandatoryValidationList'], []);

        if (!in_array($type, $mandatoryValidationList)) {
            if (is_null($validationValue) || $validationValue === false) {
                return true;
            }
        }

        $result = $this->processFieldCheck($entityType, $type, $entity, $field, $validationValue);

        if (!$result) {
            return false;
        }

        $resultRaw = $this->processFieldRawCheck($entityType, $type, $data, $field, $validationValue);

        if (!$resultRaw) {
            return false;
        }

        return true;
    }

    private function processFieldCheck(
        string $entityType, string $type, Entity $entity, string $field, $validationValue
    ) : bool {

        $checker = $this->getFieldTypeChecker($entityType, $field);

        if (!$checker) {
            return true;
        }

        $methodName = 'check' . ucfirst($type);

        if (!method_exists($checker, $methodName)) {
            return true;
        }

        return $checker->$methodName($entity, $field, $validationValue);
    }

    private function processFieldRawCheck(
        string $entityType, string $type, StdClass $data, string $field, $validationValue
    ) : bool {

        $checker = $this->getFieldTypeChecker($entityType, $field);

        if (!$checker) {
            return true;
        }

        $methodName = 'rawCheck' . ucfirst($type);

        if (!method_exists($checker, $methodName)) {
            return true;
        }

        return $checker->$methodName($data, $field, $validationValue);
    }

    private function getFieldTypeChecker(string $entityType, string $field) : ?object
    {
        $key = $entityType . '_' . $field;

        if (!array_key_exists($key, $this->checkerCache)) {
            $this->loadFieldTypeChecker($entityType, $field);
        }

        return $this->checkerCache[$key];
    }

    private function loadFieldTypeChecker(string $entityType, string $field) : void
    {
        $key = $entityType . '_' . $field;

        if (!$this->factory->isCreatable($entityType, $field)) {
            $this->checkerCache[$key] = null;

            return;
        }

        $this->checkerCache[$key] = $this->factory->create($entityType, $field);
    }
}
