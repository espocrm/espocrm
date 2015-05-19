<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 ************************************************************************/

namespace Espo\Modules\Crm\Services;

use \Espo\ORM\Entity;

class TargetList extends \Espo\Services\Record
{
    protected function loadAdditionalFields(Entity $entity)
    {
        parent::loadAdditionalFields($entity);
        $this->loadEntryCountField($entity);
    }

    public function loadAdditionalFieldsForList(Entity $entity)
    {
        parent::loadAdditionalFields($entity);
        $this->loadEntryCountField($entity);
    }

    protected function loadEntryCountField(Entity $entity)
    {
        $count = 0;
        $count += $this->getEntityManager()->getRepository('TargetList')->countRelated($entity, 'contacts');
        $count += $this->getEntityManager()->getRepository('TargetList')->countRelated($entity, 'leads');
        $count += $this->getEntityManager()->getRepository('TargetList')->countRelated($entity, 'users');
        $count += $this->getEntityManager()->getRepository('TargetList')->countRelated($entity, 'accounts');
        $entity->set('entryCount', $count);
    }

    protected function findLinkedEntitiesOptedOut($id, $link, $params)
    {
        $collection = new \Espo\ORM\EntityCollection;

        $pdo = $this->getEntityManager()->getPDO();
        $query = $this->getEntityManager()->getQuery();

        $sqlContact = $query->createSelectQuery('Contact', array(
            'select' => ['id', 'name', 'createdAt', ['VALUE:Contact', '_scope']],
            'customJoin' => 'JOIN contact_target_list AS j ON j.contact_id = contact.id AND j.deleted = 0 AND j.opted_out = 1',
            'whereClause' => array(
                'j.targetListId' => $id
            )
        ));

        $sqlLead = $query->createSelectQuery('Lead', array(
            'select' => ['id', 'name', 'createdAt', ['VALUE:Lead', '_scope']],
            'customJoin' => 'JOIN lead_target_list AS j ON j.lead_id = lead.id AND j.deleted = 0 AND j.opted_out = 1',
            'whereClause' => array(
                'j.targetListId' => $id
            )
        ));

        $sqlUser = $query->createSelectQuery('User', array(
            'select' => ['id', 'name', 'createdAt', ['VALUE:User', '_scope']],
            'customJoin' => 'JOIN target_list_user AS j ON j.user_id = user.id AND j.deleted = 0 AND j.opted_out = 1',
            'whereClause' => array(
                'j.targetListId' => $id
            )
        ));

        $sqlAccount = $query->createSelectQuery('Account', array(
            'select' => ['id', 'name', 'createdAt', ['VALUE:Account', '_scope']],
            'customJoin' => 'JOIN account_target_list AS j ON j.account_id = account.id AND j.deleted = 0 AND j.opted_out = 1',
            'whereClause' => array(
                'j.targetListId' => $id
            )
        ));

        $sql = "
            {$sqlContact}
            UNION
            {$sqlLead}
            UNION
            {$sqlUser}
            UNION
            {$sqlAccount}
            ORDER BY createdAt DESC
        ";

        $sql = $query->limit($sql, $params['offset'], $params['maxSize']);

        $sth = $pdo->prepare($sql);
        $sth->execute();
        $arr = [];
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $arr[] = $row;
        }

        $sqlCount = "SELECT COUNT(*) AS 'count' FROM ({$sql}) AS c";
        $sth = $pdo->prepare($sqlCount);
        $sth->execute();

        $row = $sth->fetch(\PDO::FETCH_ASSOC);
        $count = $row['count'];

        return array(
            'total' => $count,
            'list' => $arr
        );
    }
}

