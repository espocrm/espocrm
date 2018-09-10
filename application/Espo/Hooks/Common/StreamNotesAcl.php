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

namespace Espo\Hooks\Common;

use Espo\ORM\Entity;

class StreamNotesAcl extends \Espo\Core\Hooks\Base
{
    protected $streamService = null;

    public static $order = 10;

    protected function init()
    {
        parent::init();
        $this->addDependency('serviceFactory');
        $this->addDependency('aclManager');
    }

    protected function getServiceFactory()
    {
        return $this->getInjection('serviceFactory');
    }

    protected function getAclManager()
    {
        return $this->getInjection('aclManager');
    }

    public function afterSave(Entity $entity, array $options = [])
    {
        if (!empty($options['noStream'])) return;
        if (!empty($options['silent'])) return;

        if (!empty($options['skipStreamNotesAcl'])) return;

        if ($entity->isNew()) return;

        $entityType = $entity->getEntityType();

        if (in_array($entityType, ['Note', 'User', 'Team', 'Role', 'Portal', 'PortalRole'])) return;

        if (!$this->getMetadata()->get(['scopes', $entityType, 'acl'])) return;
        if (!$this->getMetadata()->get(['scopes', $entityType, 'object'])) return;

        $ownerUserIdAttribute = $this->getAclManager()->getImplementation($entityType)->getOwnerUserIdAttribute($entity);

        $usersAttributeIsChanged = false;
        $teamsAttributeIsChanged = false;

        if ($ownerUserIdAttribute) {
            if ($entity->isAttributeChanged($ownerUserIdAttribute)) {
                $usersAttributeIsChanged = true;
                if ($entity->getAttributeParam($ownerUserIdAttribute, 'isLinkMultipleIdList')) {
                    $userIdList = $entity->get($ownerUserIdAttribute);
                } else {
                    $userId = $entity->get($ownerUserIdAttribute);
                    if ($userId) {
                        $userIdList = [$userId];
                    } else {
                        $userIdList = [];
                    }
                }
            }
        }

        if ($entity->hasLinkMultipleField('teams') && $entity->isAttributeChanged('teamsIds')) {
            $teamsAttributeIsChanged = true;
            $teamIdList = $entity->get('teamsIds');
        }

        if ($usersAttributeIsChanged || $teamsAttributeIsChanged) {
            $noteList = $this->getEntityManager()->getRepository('Note')->where([
                'OR' => [
                    [
                        'relatedId' => $entity->id,
                        'relatedType' => $entityType
                    ],
                    [
                        'parentId' => $entity->id,
                        'parentType' => $entityType,
                        'superParentId!=' => null,
                        'relatedId' => null
                    ]
                ]
            ])->select(['id'])->find();

            foreach ($noteList as $note) {
                if ($teamsAttributeIsChanged) {
                    $note->set('teamsIds', $teamIdList);
                }
                if ($usersAttributeIsChanged) {
                    $note->set('usersIds', $userIdList);
                }
                $this->getEntityManager()->saveEntity($note);
            }
        }
    }
}
