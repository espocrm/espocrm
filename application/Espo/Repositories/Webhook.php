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

namespace Espo\Repositories;

use Espo\ORM\Entity;

use Espo\Core\Utils\Util;

class Webhook extends \Espo\Core\Repositories\Database
{
    protected $hooksDisabled = true;

    protected function beforeSave(Entity $entity, array $options = [])
    {
        if ($entity->isNew()) {
            $this->fillSecretKey($entity);
        }

        parent::beforeSave($entity);

        $this->processSettingAdditionalFields($entity);
    }

    protected function fillSecretKey(Entity $entity): void
    {
        $secretKey = Util::generateSecretKey();

        $entity->set('secretKey', $secretKey);
    }

    protected function processSettingAdditionalFields(Entity $entity): void
    {
        $event = $entity->get('event');

        if (!$event) {
            return;
        }

        $arr = explode('.', $event);

        if (count($arr) !== 2 && count($arr) !== 3) {
            return;
        }

        $arr = explode('.', $event);

        $entityType = $arr[0];
        $type = $arr[1];

        $entity->set('entityType', $entityType);
        $entity->set('type', $type);

        $field = null;

        if (!$entityType) {
            return;
        }

        if ($type === 'fieldUpdate') {
            if (count($arr) == 3) {
                $field = $arr[2];
            }

            $entity->set('field', $field);
        }
        else {
            $entity->set('field', null);
        }
    }
}
