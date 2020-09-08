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

use Espo\ORM\{
    Entity,
    Collection,
};

class Import extends \Espo\Core\Repositories\Database
{
    public function findRelated(Entity $entity, string $relationName, ?array $params = [])
    {
        $entityType = $entity->get('entityType');

        $params = $params ?? [];

        $this->addImportEntityJoin($entity, $relationName, $params);

        return $this->getEntityManager()->getRepository($entityType)->find($params);
    }

    protected function addImportEntityJoin(Entity $entity, string $link, array &$params)
    {
        $entityType = $entity->get('entityType');

        $param = null;

        switch ($link) {
            case 'imported':
                $param = 'isImported';
                break;
            case 'duplicates':
                $param = 'isDuplicate';
                break;
            case 'updated':
                $param = 'isUpdated';
                break;
            default:
                return;
        }

        $params['joins'] = $params['joins'] ?? [];

        $params['joins'][] = [
            'ImportEntity',
            'importEntity',
            [
                'importEntity.importId' => $entity->id,
                'importEntity.entityType' => $entityType,
                'importEntity.entityId:' => $entityType . '.id',
                'importEntity.' . $param => true,
            ],
        ];
    }

    public function countRelated(Entity $entity, string $relationName, ?array $params = null) : int
    {
        $entityType = $entity->get('entityType');

        $params = $params ?? [];

        $this->addImportEntityJoin($entity, $relationName, $params);

        return $this->getEntityManager()->getRepository($entityType)->count($params);
    }

    protected function afterRemove(Entity $entity, array $options = [])
    {
        if ($entity->get('fileId')) {
            $attachment = $this->getEntityManager()->getEntity('Attachment', $entity->get('fileId'));
            if ($attachment) {
                $this->getEntityManager()->removeEntity($attachment);
            }
        }

        $delete = $this->getEntityManager()->getQueryBuilder()
            ->delete()
            ->from('ImportEntity')
            ->where([
                'importId' => $entity->id,
            ])
            ->build();

        $this->getEntityManager()->getQueryExecutor()->execute($delete);

        parent::afterRemove($entity, $options);
    }
}
