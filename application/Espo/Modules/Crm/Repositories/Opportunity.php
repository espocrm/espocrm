<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

namespace Espo\Modules\Crm\Repositories;

use Espo\ORM\Entity;

class Opportunity extends \Espo\Core\ORM\Repositories\RDB
{
    public function beforeSave(Entity $entity, array $options = array())
    {
        if ($entity->isNew()) {
            if (!$entity->has('probability') && $entity->get('stage')) {
                $probability = $this->getMetadata()->get('entityDefs.Opportunity.fields.stage.probabilityMap.' . $entity->get('stage'), 0);
                $entity->set('probability', $probability);
            }
        }

        parent::beforeSave($entity, $options);
    }

    public function afterSave(Entity $entity, array $options = array())
    {
        parent::afterSave($entity, $options);
        if ($entity->isAttributeChanged('amount') || $entity->isAttributeChanged('probability')) {
            $amountConverted = $entity->get('amountConverted');
            $probability = $entity->get('probability');
            $amountWeightedConverted = round($amountConverted * $probability / 100, 2);
            $entity->set('amountWeightedConverted', $amountWeightedConverted);
        }
    }
}
