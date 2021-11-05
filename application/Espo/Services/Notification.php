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

namespace Espo\Services;

use Espo\Entities\Note;

use Espo\Core\{
    Record\Collection as RecordCollection,
    Exceptions\Error,
};

use Espo\Entities\User;

use Espo\Tools\Stream\NoteAccessControl;

class Notification extends \Espo\Services\Record
{
    protected $actionHistoryDisabled = true;

    private $noteAccessControl = null;

    public function getNotReadCount(string $userId): int
    {
        $whereClause = [
            'userId' => $userId,
            'read' => false,
        ];

        $ignoreScopeList = $this->getIgnoreScopeList();

        if (count($ignoreScopeList)) {
            $whereClause[] = [
                'OR' => [
                    'relatedParentType' => null,
                    'relatedParentType!=' => $ignoreScopeList,
                ]
            ];
        }

        return $this->entityManager
            ->getRDBRepository('Notification')
            ->where($whereClause)
            ->count();
    }

    public function markAllRead(string $userId)
    {
        $update = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in('Notification')
            ->set(['read' => true])
            ->where([
                'userId' => $userId,
                'read' => false,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);

        return true;
    }

    public function getList(string $userId, array $params = []): RecordCollection
    {
        $queryBuilder = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from('Notification');

        $whereClause = [
            'userId' => $userId,
        ];

        $user = $this->entityManager->getEntity(User::ENTITY_TYPE, $userId);

        if (!$user) {
            throw new Error("User not found.");
        }

        if (!empty($params['after'])) {
            $whereClause['createdAt>'] = $params['after'];
        }

        $ignoreScopeList = $this->getIgnoreScopeList();

        if (!empty($ignoreScopeList)) {
            $where = [];

            $where[] = [
                'OR' => [
                    'relatedParentType' => null,
                    'relatedParentType!=' => $ignoreScopeList
                ]
            ];

            $whereClause[] = $where;
        }

        $offset = $params['offset'] ?? null;
        $maxSize = $params['maxSize'] ?? null;

        $queryBuilder->limit($offset, $maxSize);

        $queryBuilder->order('createdAt', 'DESC');

        $queryBuilder->where($whereClause);

        $query = $queryBuilder->build();

        $collection = $this->entityManager
            ->getRDBRepository('Notification')
            ->clone($query)
            ->find();

        $count = $this->entityManager
            ->getRDBRepository('Notification')
            ->clone($query)
            ->count();

        $ids = [];

        foreach ($collection as $k => $entity) {
            $ids[] = $entity->getId();

            $data = $entity->get('data');

            if (empty($data)) {
                continue;
            }

            switch ($entity->get('type')) {
                case 'Note':
                case 'MentionInPost':
                    /** @var ?Note $note */
                    $note = $this->entityManager->getEntity('Note', $data->noteId);

                    if (!$note) {
                        unset($collection[$k]);

                        $count--;

                        $this->entityManager->removeEntity($entity);

                        break;
                    }

                    $this->getNoteAccessControl()->apply($note, $user);

                    if ($note->get('parentId') && $note->get('parentType')) {
                        $parent = $this->entityManager
                            ->getEntity($note->get('parentType'), $note->get('parentId'));

                        if ($parent) {
                            $note->set('parentName', $parent->get('name'));
                        }
                    }
                    else {
                        if (!$note->get('isGlobal')) {
                            $targetType = $note->get('targetType');

                            if (!$targetType || $targetType === 'users') {
                                $note->loadLinkMultipleField('users');
                            }

                            if ($targetType !== 'users') {
                                if (!$targetType || $targetType === 'teams') {
                                    $note->loadLinkMultipleField('teams');
                                }
                                else if ($targetType === 'portals') {
                                    $note->loadLinkMultipleField('portals');
                                }
                            }
                        }
                    }

                    if ($note->get('relatedId') && $note->get('relatedType')) {
                        $related = $this->entityManager
                            ->getEntity($note->get('relatedType'), $note->get('relatedId'));

                        if ($related) {
                            $note->set('relatedName', $related->get('name'));
                        }
                    }

                    $note->loadLinkMultipleField('attachments');

                    $entity->set('noteData', $note->toArray());

                    break;
            }
        }

        if (!empty($ids)) {
            $update = $this->entityManager
                ->getQueryBuilder()
                ->update()
                ->in('Notification')
                ->set(['read' => true])
                ->where([
                    'id' => $ids,
                ])
                ->build();

            $this->entityManager->getQueryExecutor()->execute($update);
        }

        return new RecordCollection($collection, $count);
    }

    private function getIgnoreScopeList(): array
    {
        $ignoreScopeList = [];

        $scopes = $this->metadata->get('scopes', []);

        foreach ($scopes as $scope => $d) {
            if (empty($d['entity'])) {
                continue;
            }

            if (empty($d['object'])) {
                continue;
            }

            if (!$this->acl->checkScope($scope)) {
                $ignoreScopeList[] = $scope;
            }
        }

        return $ignoreScopeList;
    }

    private function getNoteAccessControl(): NoteAccessControl
    {
        if (!$this->noteAccessControl) {
            $this->noteAccessControl = $this->injectableFactory->create(NoteAccessControl::class);
        }

        return $this->noteAccessControl;
    }
}
