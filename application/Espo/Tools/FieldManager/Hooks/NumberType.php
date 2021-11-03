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

namespace Espo\Tools\FieldManager\Hooks;

use Espo\Core\Di;

class NumberType implements Di\EntityManagerAware
{
    use Di\EntityManagerSetter;

    public function onRead($scope, $name, &$defs, $options)
    {
        $number = $this->entityManager
            ->getRDBRepository('NextNumber')
            ->where([
                'entityType' => $scope,
                'fieldName' => $name,
            ])
            ->findOne();

        $value = null;

        if (!$number) {
            $value = 1;
        } else {
            if (!$number->get('value')) {
                $value = 1;
            }
        }

        if (!$value && $number) {
            $value = $number->get('value');
        }

        $defs['nextNumber'] = $value;
    }

    public function afterSave($scope, $name, $defs, $options)
    {
        if (!isset($defs['nextNumber'])) {
            return;
        }

        $number = $this->entityManager
            ->getRDBRepository('NextNumber')
            ->where([
                'entityType' => $scope,
                'fieldName' => $name
            ])
            ->findOne();

        if (!$number) {
            $number = $this->entityManager->getEntity('NextNumber');

            $number->set('entityType', $scope);
            $number->set('fieldName', $name);
        }

        $number->set('value', $defs['nextNumber']);

        $this->entityManager->saveEntity($number);
    }

    public function afterRemove($scope, $name, $defs, $options)
    {
        $number = $this->entityManager
            ->getRDBRepository('NextNumber')
            ->where([
                'entityType' => $scope,
                'fieldName' => $name
            ])
            ->findOne();

        if (!$number) {
            return;
        }

        $this->entityManager->removeEntity($number);
    }
}
