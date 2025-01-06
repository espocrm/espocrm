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

namespace Espo\Repositories;

use Espo\Core\Repositories\Database;
use Espo\Entities\LayoutRecord;
use Espo\Entities\LayoutSet as LayoutSetEntity;
use Espo\ORM\Entity;

/**
 * @extends Database<LayoutSetEntity>
 */
class LayoutSet extends Database
{
    protected function afterSave(Entity $entity, array $options = [])
    {
        parent::afterSave($entity);

        if (!$entity->isNew() && $entity->has('layoutList')) {
            $listBefore = $entity->getFetched('layoutList') ?? [];
            $listNow = $entity->get('layoutList') ?? [];

            foreach ($listBefore as $name) {
                if (!in_array($name, $listNow)) {
                    $layout = $this->entityManager
                        ->getRDBRepository(LayoutRecord::ENTITY_TYPE)
                        ->where([
                            'layoutSetId' => $entity->getId(),
                            'name' => $name,
                        ])
                        ->findOne();

                    if ($layout) {
                        $this->entityManager->removeEntity($layout);
                    }
                }
            }
        }
    }

    protected function afterRemove(Entity $entity, array $options = [])
    {
        parent::afterRemove($entity);

        $layoutList = $this->entityManager
            ->getRDBRepository(LayoutRecord::ENTITY_TYPE)
            ->where([
                'layoutSetId' => $entity->getId(),
            ])
            ->find();

        foreach ($layoutList as $layout) {
            $this->entityManager->removeEntity($layout);
        }
    }
}
