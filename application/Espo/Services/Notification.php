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

namespace Espo\Services;

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

use Espo\ORM\Entity;

use Espo\Core\Utils\Json;

class Notification extends \Espo\Services\Record
{
    protected $actionHistoryDisabled = true;

    protected function init()
    {
        parent::init();
        $this->addDependency('container');
    }

    public function notifyAboutMentionInPost($userId, $noteId)
    {
        $notification = $this->getEntityManager()->getEntity('Notification');
        $notification->set(array(
            'type' => 'MentionInPost',
            'data' => array('noteId' => $noteId),
            'userId' => $userId,
            'relatedId' => $noteId,
            'relatedType' => 'Note'
        ));
        $this->getEntityManager()->saveEntity($notification);
    }

    public function notifyAboutNote(array $userIdList, \Espo\Entities\Note $note)
    {
        $data = ['noteId' => $note->id];
        $encodedData = Json::encode($data);

        $related = null;
        if ($note->get('relatedType') == 'Email') {
            $related = $this->getEntityManager()->getRepository('Email')
                ->select(['id', 'sentById', 'createdById'])->where(['id' => $note->get('relatedId')])->findOne();
        }

        $now = date('Y-m-d H:i:s');
        $pdo = $this->getEntityManager()->getPDO();

        $query = $this->getEntityManager()->getQuery();

        $sql = "INSERT INTO `notification` (`id`, `data`, `type`, `user_id`, `created_at`, `related_id`, `related_type`, `related_parent_id`, `related_parent_type`) VALUES ";
        $arr = [];

        $userList = $this->getEntityManager()->getRepository('User')->where([
            'isActive' => true,
            'id' => $userIdList
        ])->find();

        foreach ($userList as $user) {
            $userId = $user->id;
            if (!$this->checkUserNoteAccess($user, $note)) continue;
            if ($note->get('createdById') === $user->id) continue;
            if ($related && $related->getEntityType() == 'Email' && $related->get('sentById') == $user->id) continue;
            if ($related && $related->get('createdById') == $user->id) continue;

            $id = \Espo\Core\Utils\Util::generateId();
            $arr[] = "(".$query->quote($id).", ".$query->quote($encodedData).", ".$query->quote('Note').", ".$query->quote($userId).", ".$query->quote($now).", ".$query->quote($note->id).", ".$query->quote('Note').", ".$query->quote($note->get('parentId')).", ".$query->quote($note->get('parentType')).")";
        }

        if (empty($arr)) return;

        $sql .= implode(", ", $arr);
        $pdo->query($sql);

        if ($this->getConfig()->get('useWebSocket')) {
            foreach ($userIdList as $userId) {
                $this->getInjection('container')->get('webSocketSubmission')->submit('newNotification', $userId);
            }
        }
    }

    public function checkUserNoteAccess(\Espo\Entities\User $user, \Espo\Entities\Note $note)
    {
        if ($user->isPortal()) {
            if ($note->get('relatedType')) {
                if ($note->get('relatedType') === 'Email' && $note->get('parentType') === 'Case') {
                    return true;
                }
                return false;
            }
            return true;
        }

        if ($note->get('relatedType')) {
            if (!$this->getAclManager()->checkScope($user, $note->get('relatedType'))) {
                return false;
            }
        }

        if ($note->get('parentType')) {
            if (!$this->getAclManager()->checkScope($user, $note->get('parentType'))) {
                return false;
            }
        }

        return true;
    }

    public function getNotReadCount($userId)
    {
        $whereClause = array(
            'userId' => $userId,
            'read' => 0
        );

        $ignoreScopeList = $this->getIgnoreScopeList();
        if (!empty($ignoreScopeList)) {
            $where = [];
            $where[] = array(
                'OR' => array(
                    'relatedParentType' => null,
                    'relatedParentType!=' => $ignoreScopeList
                )
            );
            $whereClause[] = $where;
        }

        return $this->getEntityManager()->getRepository('Notification')->where($whereClause)->count();
    }

    public function markAllRead($userId)
    {
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "UPDATE notification SET `read` = 1 WHERE user_id = ".$pdo->quote($userId)." AND `read` = 0";
        $pdo->prepare($sql)->execute();
        return true;
    }

    public function getList($userId, array $params = array())
    {
        $searchParams = array();

        $whereClause = array(
            'userId' => $userId
        );
        if (!empty($params['after'])) {
            $whereClause['createdAt>'] = $params['after'];
        }

        $ignoreScopeList = $this->getIgnoreScopeList();
        if (!empty($ignoreScopeList)) {
            $where = [];
            $where[] = array(
                'OR' => array(
                    'relatedParentType' => null,
                    'relatedParentType!=' => $ignoreScopeList
                )
            );
            $whereClause[] = $where;
        }

        $searchParams['whereClause'] = $whereClause;

        if (array_key_exists('offset', $params)) {
            $searchParams['offset'] = $params['offset'];
        }
        if (array_key_exists('maxSize', $params)) {
            $searchParams['limit'] = $params['maxSize'];
        }
        $searchParams['orderBy'] = 'createdAt';
        $searchParams['order'] = 'DESC';

        $collection = $this->getEntityManager()->getRepository('Notification')->find($searchParams);
        $count = $this->getEntityManager()->getRepository('Notification')->count($searchParams);

        $ids = array();
        foreach ($collection as $k => $entity) {
            $ids[] = $entity->id;
            $data = $entity->get('data');
            if (empty($data)) {
                continue;
            }
            switch ($entity->get('type')) {
                case 'Note':
                case 'MentionInPost':
                    $note = $this->getEntityManager()->getEntity('Note', $data->noteId);
                    if ($note) {
                        if ($note->get('parentId') && $note->get('parentType')) {
                            $parent = $this->getEntityManager()->getEntity($note->get('parentType'), $note->get('parentId'));
                            if ($parent) {
                                $note->set('parentName', $parent->get('name'));
                            }
                        } else {
                            if (!$note->get('isGlobal')) {
                                $targetType = $note->get('targetType');
                                if (!$targetType || $targetType === 'users') {
                                    $note->loadLinkMultipleField('users');
                                }
                                if ($targetType !== 'users') {
                                    if (!$targetType || $targetType === 'teams') {
                                        $note->loadLinkMultipleField('teams');
                                    } else if ($targetType === 'portals') {
                                        $note->loadLinkMultipleField('portals');
                                    }
                                }
                            }
                        }
                        if ($note->get('relatedId') && $note->get('relatedType')) {
                            $related = $this->getEntityManager()->getEntity($note->get('relatedType'), $note->get('relatedId'));
                            if ($related) {
                                $note->set('relatedName', $related->get('name'));
                            }
                        }
                        $note->loadLinkMultipleField('attachments');
                        $entity->set('noteData', $note->toArray());
                    } else {
                        unset($collection[$k]);
                        $count--;
                        $this->getEntityManager()->removeEntity($entity);
                    }
                    break;
            }
        }

        if (!empty($ids)) {
            $pdo = $this->getEntityManager()->getPDO();
            $idQuotedList = [];
            foreach ($ids as $id) {
                $idQuotedList[] = $pdo->quote($id);
            }

            $sql = "UPDATE notification SET `read` = 1 WHERE id IN (" . implode(', ', $idQuotedList) .")";

            $s = $pdo->prepare($sql);
            $s->execute();
        }


        return array(
            'total' => $count,
            'collection' => $collection
        );
    }

    protected function getIgnoreScopeList()
    {
        $ignoreScopeList = [];
        $scopes = $this->getMetadata()->get('scopes', array());
        foreach ($scopes as $scope => $d) {
            if (empty($d['entity']) || !$d['entity']) continue;
            if (empty($d['object']) || !$d['object']) continue;
            if (!$this->getAcl()->checkScope($scope)) {
                $ignoreScopeList[] = $scope;
            }
        }
        return $ignoreScopeList;
    }
}

