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

namespace Espo\Core\Utils;

class FieldValidatorManager
{
    private $metadata;

    private $fieldManagerUtil;

    private $implHash = [];

    public function __construct(Metadata $metadata, FieldManagerUtil $fieldManagerUtil)
    {
        $this->metadata = $metadata;
        $this->fieldManagerUtil = $fieldManagerUtil;
    }

    public function check(\Espo\ORM\Entity $entity, string $field, string $type, $data = null) : bool
    {
        if (!$data) $data = (object) [];

        $fieldType = $this->fieldManagerUtil->getEntityTypeFieldParam($entity->getEntityType(), $field, 'type');

        $validationValue = $this->fieldManagerUtil->getEntityTypeFieldParam($entity->getEntityType(), $field, $type);

        $mandatoryValidationList = $this->metadata->get(['fields', $fieldType, 'mandatoryValidationList'], []);

        if (!in_array($type, $mandatoryValidationList)) {
            if (is_null($validationValue) || $validationValue === false) return true;
        }

        if (!array_key_exists($fieldType, $this->implHash)) {
            $this->loadImpl($fieldType);
        }
        $impl = $this->implHash[$fieldType];

        $methodName = 'check' . ucfirst($type);

        if (!method_exists($impl, $methodName)) return true;

        return $impl->$methodName($entity, $field, $validationValue, $data);
    }

    protected function loadImpl(string $fieldType)
    {
        $className = $this->metadata->get(['fields', $fieldType, 'validatorClassName']);

        if (!$className) {
            $className = '\\Espo\\Core\\FieldValidators\\' . ucfirst($fieldType) . 'Type';
            if (!class_exists($className)) {
                $className = '\\Espo\\Core\\FieldValidators\\BaseType';
            }
        }

        $this->implHash[$fieldType] = new $className($this->metadata, $this->fieldManagerUtil);
    }
}
