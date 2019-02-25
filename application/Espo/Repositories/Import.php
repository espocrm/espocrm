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

namespace Espo\Repositories;

use Espo\ORM\Entity;

class Import extends \Espo\Core\ORM\Repositories\RDB
{
    public function findRelated(Entity $entity, $relationName, array $params = array())
    {
        $entityType = $entity->get('entityType');

        if (empty($params['customJoin'])) {
            $params['customJoin'] = '';
        }
        $params['customJoin'] .= $this->getRelatedJoin($entity, $relationName);

        return $this->getEntityManager()->getRepository($entityType)->find($params);
    }

    protected function getRelatedJoin(Entity $entity, $link)
    {
        $entityType = $entity->get('entityType');
        $pdo = $this->getEntityManager()->getPDO();
        $table = $this->getEntityManager()->getQuery()->toDb($this->getEntityManager()->getQuery()->sanitize($entityType));

        $part = "0";
        switch ($link) {
            case 'imported':
                $part = "import_entity.is_imported = 1";
                break;
            case 'duplicates':
                $part = "import_entity.is_duplicate = 1";
                break;
            case 'updated':
                $part = "import_entity.is_updated = 1";
                break;
        }


        $sql = "
            JOIN import_entity ON
                import_entity.import_id = " . $pdo->quote($entity->id) . " AND
                import_entity.entity_type = " . $pdo->quote($entity->get('entityType')) . " AND
                import_entity.entity_id = " . $table . ".id AND
                ".$part."
        ";

        return $sql;
    }

    public function countRelated(Entity $entity, $relationName, array $params = array())
    {
        $entityType = $entity->get('entityType');

        if (empty($params['customJoin'])) {
            $params['customJoin'] = '';
        }
        $params['customJoin'] .= $this->getRelatedJoin($entity, $relationName);

        return $this->getEntityManager()->getRepository($entityType)->count($params);
    }

    protected function afterRemove(Entity $entity, array $options = array())
    {
        if ($entity->get('fileId')) {
            $attachment = $this->getEntityManager()->getEntity('Attachment', $entity->get('fileId'));
            if ($attachment) {
                $this->getEntityManager()->removeEntity($attachment);
            }
        }

        $pdo = $this->getEntityManager()->getPDO();
        $sql = "DELETE FROM import_entity WHERE import_id = :importId";
        $sth = $pdo->prepare($sql);
        $sth->bindValue(':importId', $entity->id);
        $sth->execute();

        parent::afterRemove($entity, $options);

    }

}

