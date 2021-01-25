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

namespace Espo\Modules\Crm\Services;

use Espo\ORM\Entity;

use Espo\ORM\QueryParams\Select;

use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;

use Espo\Core\Record\Collection as RecordCollection;

use PDO;

use Espo\Core\Di;

class TargetList extends \Espo\Services\Record implements

    Di\HookManagerAware
{
    use Di\HookManagerSetter;

    protected $noEditAccessRequiredLinkList = ['accounts', 'contacts', 'leads', 'users'];

    protected $duplicatingLinkList = ['accounts', 'contacts', 'leads', 'users'];

    protected $targetsLinkList = ['contacts', 'leads', 'users', 'accounts'];

    protected $linkMandatorySelectAttributeList = [
        'accounts' => ['targetListIsOptedOut'],
        'contacts' => ['targetListIsOptedOut'],
        'leads' => ['targetListIsOptedOut'],
        'users' => ['targetListIsOptedOut'],
    ];

    protected $entityTypeLinkMap = [
        'Lead' => 'leads',
        'Account' => 'accounts',
        'Contact' => 'contacts',
        'User' => 'users',
    ];

    public function loadAdditionalFields(Entity $entity)
    {
        parent::loadAdditionalFields($entity);
        $this->loadEntryCountField($entity);
        $this->loadOptedOutCountField($entity);
    }

    public function loadAdditionalFieldsForList(Entity $entity)
    {
        parent::loadAdditionalFields($entity);
        $this->loadEntryCountField($entity);
    }

    protected function loadEntryCountField(Entity $entity)
    {
        $count = 0;
        foreach ($this->targetsLinkList as $link) {
            $count += $this->getEntityManager()->getRepository('TargetList')->countRelated($entity, $link);
        }
        $entity->set('entryCount', $count);
    }

    protected function loadOptedOutCountField(Entity $entity)
    {
        $count = 0;

        foreach ($this->targetsLinkList as $link) {
            $foreignEntityType = $entity->getRelationParam($link, 'entity');

            $count += $this->getEntityManager()->getRepository($foreignEntityType)
                ->join('targetLists')
                ->where([
                    'targetListsMiddle.targetListId' => $entity->id,
                    'targetListsMiddle.optedOut' => 1,
                ])
                ->count();
        }

        $entity->set('optedOutCount', $count);
    }

    protected function afterCreateEntity(Entity $entity, $data)
    {
        if (property_exists($data, 'sourceCampaignId') && !empty($data->includingActionList)) {
            $excludingActionList = [];
            if (!empty($data->excludingActionList)) {
                $excludingActionList = $data->excludingActionList;
            }
            $this->populateFromCampaignLog($entity, $data->sourceCampaignId, $data->includingActionList, $excludingActionList);
        }
    }

    protected function populateFromCampaignLog(
        Entity $entity, string $sourceCampaignId, array $includingActionList, array $excludingActionList
    ) {
        if (empty($sourceCampaignId)) {
            throw new BadRequest();
        }

        $campaign = $this->getEntityManager()->getEntity('Campaign', $sourceCampaignId);

        if (!$campaign) {
            throw new NotFound();
        }

        if (!$this->getAcl()->check($campaign, 'read')) {
            throw new Forbidden();
        }

        $queryBuilder = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from('CampaignLogRecord')
            ->where([
                'isTest' => false,
                'campaignId' => $sourceCampaignId,
            ])
            ->select(['id', 'parentId', 'parentType']);

        $notQueryBuilder = clone $queryBuilder;

        $queryBuilder->where([
            'action=' => $includingActionList,
        ]);

        $queryBuilder->groupBy([
            'parentId',
            'parentType',
            'id',
        ]);

        $notQueryBuilder->where([
            'action=' => $excludingActionList,
        ]);

        $notQueryBuilder->select(['id']);

        $list = $this->getEntityManager()
            ->getRepository('CampaignLogRecord')
            ->clone($queryBuilder->build())
            ->find();

        foreach ($list as $logRecord) {
            if (!$logRecord->get('parentType')) {
                continue;
            }

            if (empty($this->entityTypeLinkMap[$logRecord->get('parentType')])) {
                continue;
            }

            if (!empty($excludingActionList)) {
                $cloneQueryBuilder = clone $notQueryBuilder;


                $cloneQueryBuilder->where([
                    'parentType' => $logRecord->get('parentType'),
                    'parentId' => $logRecord->get('parentId'),
                ]);

                if (
                    $this->getEntityManager()
                        ->getRepository('CampaignLogRecord')
                        ->clone($cloneQueryBuilder->build())
                        ->findOne()
                ) {
                    continue;
                }
            }

            $relation = $this->entityTypeLinkMap[$logRecord->get('parentType')];

            $this->getRepository()->relate($entity, $relation, $logRecord->get('parentId'));
        }
    }

    public function unlinkAll(string $id, string $link)
    {
        $entity = $this->getRepository()->get($id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }

        $foreignEntityType = $entity->getRelationParam($link, 'entity');
        if (!$foreignEntityType) {
            throw new Error();
        }

        if (empty($foreignEntityType)) {
            throw new Error();
        }

        $pdo = $this->getEntityManager()->getPDO();
        $query = $this->getEntityManager()->getQueryComposer();
        $sql = null;

        $linkEntityType = ucfirst(
            $entity->getRelationParam($link, 'relationName') ?? ''
        );

        if ($linkEntityType === '') {
            throw new Error();
        }

        $updateQuery = $this->getEntityManager()->getQueryBuilder()
            ->update()
            ->in($linkEntityType)
            ->set([
                'deleted' => true,
            ])
            ->where([
                'targetListId' => $entity->id,
            ])
            ->build();

        $this->getEntityManager()->getQueryExecutor()->execute($updateQuery);

        $this->hookManager->process('TargetList', 'afterUnlinkAll', $entity, [], ['link' => $link]);

        return true;
    }

    protected function getOptedOutSelectQueryForLink(string $targetListId, string $link) : Select
    {
        $seed = $this->getRepository()->getNew();

        $entityType = $seed->getRelationParam($link, 'entity');

        if (!$entityType) {
            throw new Error();
        }

        $linkEntityType = ucfirst(
            $seed->getRelationParam($link, 'relationName') ?? ''
        );

        if ($linkEntityType === '') {
            throw new Error();
        }

        $key = $seed->getRelationParam($link, 'midKeys')[1] ?? null;


        if (!$key) {
            throw new Error();
        }

        return $this->getEntityManager()->getQueryBuilder()
            ->select()
            ->from($entityType)
            ->select(['id', 'name', 'createdAt', ["'{$entityType}'", 'entityType']])
            ->join(
                $linkEntityType,
                'j',
                [
                    "j.{$key}:" => 'id',
                    'j.deleted' => false,
                    'j.optedOut' => true,
                    'j.targetListId' => $targetListId,
                ]
            )
            ->order('createdAt', 'DESC')
            ->build();
    }

    protected function findLinkedOptedOut(string $id, array $params) : RecordCollection
    {
        $offset = $params['offset'] ?? 0;
        $maxSize = $params['maxSize'] ?? 0;

        $em = $this->getEntityManager();
        $queryBuilder = $em->getQueryBuilder();

        $queryList = [];

        foreach ($this->targetsLinkList as $link) {
            $queryList[] = $this->getOptedOutSelectQueryForLink($id, $link);
        }

        $builder = $queryBuilder
            ->union()
            ->all();

        foreach ($queryList as $query) {
            $builder->query($query);
        }

        $countQuery = $queryBuilder
            ->select()
            ->fromQuery($builder->build(), 'c')
            ->select('COUNT:(c.id)', 'count')
            ->build();

        $sth = $em->getQueryExecutor()->execute($countQuery);

        $row = $sth->fetch(PDO::FETCH_ASSOC);

        $totalCount = $row['count'];

        $unionQuery = $builder
            ->limit($offset, $maxSize)
            ->order('createdAt', 'DESC')
            ->build();

        $sth = $em->getQueryExecutor()->execute($unionQuery);

        $collection = $this->getEntityManager()->createCollection();

        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $itemEntity = $this->getEntityManager()->getEntity($row['entityType']);

            $itemEntity->set($row);
            $itemEntity->setAsFetched();

            $collection[] = $itemEntity;
        }

        return new RecordCollection($collection, $totalCount);
    }

    public function optOut(string $id, string $targetType, string $targetId)
    {
        $targetList = $this->getEntityManager()->getEntity('TargetList', $id);

        if (!$targetList) {
            throw new NotFound();
        }

        $target = $this->getEntityManager()->getEntity($targetType, $targetId);

        if (!$target) {
            throw new NotFound();
        }

        $map = [
            'Account' => 'accounts',
            'Contact' => 'contacts',
            'Lead' => 'leads',
            'User' => 'users',
        ];

        if (empty($map[$targetType])) {
            throw new Error();
        }

        $link = $map[$targetType];

        $result = $this->getEntityManager()->getRepository('TargetList')->relate($targetList, $link, $targetId, array(
            'optedOut' => true
        ));

        if (!$result) {
            return false;
        }

        $hookData = [
           'link' => $link,
           'targetId' => $targetId,
           'targetType' => $targetType
        ];

        $this->hookManager->process('TargetList', 'afterOptOut', $targetList, [], $hookData);

        return true;
    }

    public function cancelOptOut(string $id, string $targetType, string $targetId)
    {
        $targetList = $this->getEntityManager()->getEntity('TargetList', $id);

        if (!$targetList) {
            throw new NotFound();
        }

        $target = $this->getEntityManager()->getEntity($targetType, $targetId);

        if (!$target) {
            throw new NotFound();
        }

        $map = [
            'Account' => 'accounts',
            'Contact' => 'contacts',
            'Lead' => 'leads',
            'User' => 'users',
        ];

        if (empty($map[$targetType])) {
            throw new Error();
        }
        $link = $map[$targetType];

        $result = $this->getEntityManager()->getRepository('TargetList')->updateRelation($targetList, $link, $targetId, array(
            'optedOut' => false
        ));

        if (!$result) {
            return false;
        }

        $hookData = [
           'link' => $link,
           'targetId' => $targetId,
           'targetType' => $targetType
        ];

        $this->hookManager->process('TargetList', 'afterCancelOptOut', $targetList, [], $hookData);

        return true;
    }

    /**
     * @todo Don't use additionalColumnsConditions.
     */
    protected function duplicateLinks(Entity $entity, Entity $duplicatingEntity)
    {
        $repository = $this->getRepository();

        foreach ($this->duplicatingLinkList as $link) {
            $linkedList = $repository->findRelated($duplicatingEntity, $link, array(
                'additionalColumnsConditions' => array(
                    'optedOut' => false
                )
            ));
            foreach ($linkedList as $linked) {
                $repository->relate($entity, $link, $linked, array(
                    'optedOut' => false
                ));
            }
        }
    }
}
