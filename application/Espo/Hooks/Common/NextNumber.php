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

namespace Espo\Hooks\Common;

use Espo\ORM\Entity;
use Espo\Core\Utils\Util;

class NextNumber extends \Espo\Core\Hooks\Base
{
    public static $order = 9;

    protected function init()
    {
        $this->addDependency('metadata');
    }

    protected function getMetadata()
    {
        return $this->getInjection('metadata');
    }

    protected function composeNumberAttribute(Entity $nextNumber)
    {
        $entityType = $nextNumber->get('entityType');
        $fieldName = $nextNumber->get('fieldName');
        $value = $nextNumber->get('value');

        $prefix = $this->getMetadata()->get(['entityDefs', $entityType, 'fields', $fieldName, 'prefix'], '');
        $padLength = $this->getMetadata()->get(['entityDefs', $entityType, 'fields', $fieldName, 'padLength'], 0);

        return $prefix . str_pad(strval($value), $padLength, '0', \STR_PAD_LEFT);
    }

    public function beforeSave(Entity $entity, array $options = array())
    {
        $fieldDefs = $this->getMetadata()->get(['entityDefs', $entity->getEntityType(), 'fields'], array());

        foreach ($fieldDefs as $fieldName => $defs) {
            if (isset($defs['type']) && $defs['type'] === 'number') {
                if (!empty($options['import'])) {
                    if ($entity->has($fieldName)) {
                        continue;
                    }
                }
                if (!$entity->isNew()) {
                    if ($entity->isAttributeChanged($fieldName)) {
                        $entity->set($fieldName, $entity->getFetched($fieldName));
                    }
                    continue;
                }
                $this->getEntityManager()->getPdo()->query('LOCK TABLES `next_number` WRITE');
                $nextNumber = $this->getEntityManager()->getRepository('NextNumber')->where(array(
                    'fieldName' => $fieldName,
                    'entityType' => $entity->getEntityType()
                ))->findOne();
                if (!$nextNumber) {
                    $nextNumber = $this->getEntityManager()->getEntity('NextNumber');
                    $nextNumber->set('entityType', $entity->getEntityType());
                    $nextNumber->set('fieldName', $fieldName);
                }
                $entity->set($fieldName, $this->composeNumberAttribute($nextNumber));

                $value = $nextNumber->get('value');
                if (!$value) {
                    $value = 1;
                }
                $value++;

                $nextNumber->set('value', $value);
                $this->getEntityManager()->saveEntity($nextNumber);

                $this->getEntityManager()->getPdo()->query('UNLOCK TABLES');
            }
        }
    }

}

