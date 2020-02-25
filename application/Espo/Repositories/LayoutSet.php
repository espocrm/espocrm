<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

class LayoutSet extends \Espo\Core\ORM\Repositories\RDB
{
    protected function afterSave(Entity $entity, array $options = [])
    {
        parent::afterSave($entity);

        if (!$entity->isNew() && $entity->has('layoutList')) {
            $listBefore = $entity->getFetched('layoutList') ?? [];
            $listNow = $entity->get('layoutList') ?? [];

            foreach ($listBefore as $name) {
                if (!in_array($name, $listNow)) {
                    $layout = $this->getEntityManager()->getRepository('LayoutRecord')->where([
                        'layoutSetId' => $entity->id,
                        'name' => $name,
                    ])->findOne();
                    if ($layout) {
                        $this->getEntityManager()->removeEntity($layout);
                    }
                }
            }
        }
    }

    protected function afterRemove(Entity $entity, array $options = [])
    {
        $layoutList = $this->getEntityManager()->getRepository('LayoutRecord')->where([
            'layoutSetId' => $entity->id,
        ])->find();

        foreach ($layoutList as $layout) {
            $this->getEntityManager()->removeEntity($layout);
        }
    }
}
