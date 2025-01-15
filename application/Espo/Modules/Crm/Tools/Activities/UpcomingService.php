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

namespace Espo\Modules\Crm\Tools\Activities;

use Espo\Core\Acl;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Select\Bool\Filters\OnlyMy;
use Espo\Core\Select\Bool\Filters\Shared;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Preferences;
use Espo\Entities\User;
use Espo\Modules\Crm\Classes\Select\Meeting\PrimaryFilters\Planned;
use Espo\Modules\Crm\Classes\Select\Task\PrimaryFilters\Actual;
use Espo\Modules\Crm\Entities\Task;
use Espo\Modules\Crm\Tools\Activities\Upcoming\Params;
use Espo\ORM\EntityCollection;
use Espo\ORM\EntityManager;
use Espo\ORM\Entity;
use Espo\ORM\Query\Select;
use Espo\Core\Acl\Table;
use Espo\Core\Record\Collection as RecordCollection;
use Espo\Core\Select\Where\Item as WhereItem;
use Espo\Core\Select\Where\ConverterFactory as WhereConverterFactory;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\ORM\Query\SelectBuilder;

use Exception;
use PDO;
use DateTime;
use RuntimeException;

class UpcomingService
{
    private const UPCOMING_ACTIVITIES_FUTURE_DAYS = 1;
    private const UPCOMING_ACTIVITIES_TASK_FUTURE_DAYS = 7;

    public function __construct(
        private WhereConverterFactory $whereConverterFactory,
        private SelectBuilderFactory $selectBuilderFactory,
        private Config $config,
        private Metadata $metadata,
        private Acl $acl,
        private EntityManager $entityManager,
        private ServiceContainer $serviceContainer,
        private Config\ApplicationConfig $applicationConfig,
    ) {}

    /**
     * Get upcoming activities.
     *
     * @return RecordCollection<Entity>
     * @throws Forbidden
     * @throws NotFound
     * @throws BadRequest
     */
    public function get(string $userId, Params $params): RecordCollection
    {
        /** @var ?User $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

        if (!$user) {
            throw new NotFound();
        }

        $this->accessCheck($user);

        $entityTypeList = $params->entityTypeList ?? $this->config->get('activitiesEntityList', []);

        $futureDays = $params->futureDays ??
            $this->config->get('activitiesUpcomingFutureDays', self::UPCOMING_ACTIVITIES_FUTURE_DAYS);

        $queryList = [];

        foreach ($entityTypeList as $entityType) {
            if (
                !$this->metadata->get(['scopes', $entityType, 'activity']) &&
                $entityType !== Task::ENTITY_TYPE
            ) {
                continue;
            }

            if (
                !$this->acl->checkScope($entityType, Table::ACTION_READ) ||
                !$this->metadata->get(['entityDefs', $entityType, 'fields', 'dateStart']) ||
                !$this->metadata->get(['entityDefs', $entityType, 'fields', 'dateEnd'])
            ) {
                continue;
            }

            $queryList[] = $this->getEntityTypeQuery($entityType, $user, $futureDays, $params->includeShared);
        }

        if ($queryList === []) {
            return RecordCollection::create(new EntityCollection(), 0);
        }

        $builder = $this->entityManager
            ->getQueryBuilder()
            ->union();

        foreach ($queryList as $query) {
            $builder->query($query);
        }

        $unionCountQuery = $builder->build();

        $countQuery = $this->entityManager->getQueryBuilder()
            ->select()
            ->fromQuery($unionCountQuery, 'c')
            ->select('COUNT:(c.id)', 'count')
            ->build();

        $countSth = $this->entityManager->getQueryExecutor()->execute($countQuery);

        $row = $countSth->fetch(PDO::FETCH_ASSOC);

        $totalCount = $row['count'];

        $offset = $params->offset ?? 0;
        $maxSize = $params->maxSize ?? 0;

        $unionQuery = $builder
            ->order('dateEndIsNull')
            ->order('order')
            ->order('dateStart')
            ->order('dateEnd')
            ->order('name')
            ->limit($offset, $maxSize)
            ->build();

        $sth = $this->entityManager->getQueryExecutor()->execute($unionQuery);

        $rows = $sth->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $collection = new EntityCollection();

        foreach ($rows as $row) {
            /** @var string $itemEntityType */
            $itemEntityType = $row['entityType'];
            /** @var string $itemId */
            $itemId = $row['id'];

            $entity = $this->entityManager->getEntityById($itemEntityType, $itemId);

            if (!$entity) {
                // @todo Revise.
                $entity = $this->entityManager->getNewEntity($itemEntityType);

                $entity->set('id', $itemId);
            }

            if (
                $entity instanceof CoreEntity &&
                $entity->hasLinkParentField(Field::PARENT)
            ) {
                $entity->loadParentNameField(Field::PARENT);
            }

            $this->serviceContainer->get($itemEntityType)->prepareEntityForOutput($entity);

            $collection->append($entity);
        }

        /** @var RecordCollection<Entity> */
        return RecordCollection::create($collection, $totalCount);
    }

    /**
     * @throws Forbidden
     * @throws BadRequest
     */
    private function getEntityTypeQuery(string $entityType, User $user, int $futureDays, bool $includeShared): Select
    {
        try {
            $beforeString = (new DateTime())->modify('+' . $futureDays . ' days')
                ->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        $builder = $this->selectBuilderFactory
            ->create()
            ->from($entityType)
            ->forUser($user)
            ->withBoolFilter(OnlyMy::NAME)
            ->withStrictAccessControl();

        if ($includeShared && $this->metadata->get("scopes.$entityType.collaborators")) {
            $builder->withBoolFilter(Shared::NAME);
        }

        $orderField = 'dateStart';
        $primaryFilter = Planned::NAME;

        if ($entityType === Task::ENTITY_TYPE) {
            $orderField = 'dateEnd';
            $primaryFilter = Actual::NAME;
        }

        $builder->withPrimaryFilter($primaryFilter);

        $queryBuilder = $builder->buildQueryBuilder();

        $this->apply($entityType, $user, $queryBuilder, $beforeString);

        $queryBuilder->select([
            'id',
            'name',
            'dateStart',
            'dateEnd',
            ['"' . $entityType . '"', 'entityType'],
            ['IS_NULL:(dateEnd)', 'dateEndIsNull'],
            [$orderField, 'order'],
        ]);

        return $queryBuilder->build();
    }

    private function getUserTimeZone(User $user): string
    {
        $preferences = $this->entityManager->getEntityById(Preferences::ENTITY_TYPE, $user->getId());

        if ($preferences) {
            $timeZone = $preferences->get('timeZone');

            if ($timeZone) {
                return $timeZone;
            }
        }

        return $this->applicationConfig->getTimeZone();
    }

    /**
     * @throws Forbidden
     */
    private function accessCheck(Entity $entity): void
    {
        if ($entity instanceof User) {
            if (!$this->acl->checkUserPermission($entity, Acl\Permission::USER_CALENDAR)) {
                throw new Forbidden();
            }

            return;
        }

        if (!$this->acl->check($entity, Table::ACTION_READ)) {
            throw new Forbidden();
        }
    }

    /**
     * @throws BadRequest
     */
    private function applyTask(
        User $user,
        SelectBuilder $queryBuilder,
        string $beforeString
    ): void {

        $converter = $this->whereConverterFactory->create(Task::ENTITY_TYPE, $user);
        $timeZone = $this->getUserTimeZone($user);

        $upcomingTaskFutureDays = $this->config->get(
            'activitiesUpcomingTaskFutureDays',
            self::UPCOMING_ACTIVITIES_TASK_FUTURE_DAYS
        );

        $taskBeforeString = (new DateTime())
            ->modify('+' . $upcomingTaskFutureDays . ' days')
            ->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

        $queryBuilder->where([
            'OR' => [
                [
                    'dateStart' => null,
                    'OR' => [
                        'dateEnd' => null,
                        $converter->convert(
                            $queryBuilder,
                            WhereItem::fromRaw([
                                'type' => 'before',
                                'attribute' => 'dateEnd',
                                'value' => $taskBeforeString,
                            ])
                        )->getRaw()
                    ]
                ],
                [
                    'dateStart!=' => null,
                    'OR' => [
                        $converter->convert(
                            $queryBuilder,
                            WhereItem::fromRaw([
                                'type' => 'past',
                                'attribute' => 'dateStart',
                                'dateTime' => true,
                                'timeZone' => $timeZone,
                            ])
                        )->getRaw(),
                        $converter->convert(
                            $queryBuilder,
                            WhereItem::fromRaw([
                                'type' => 'today',
                                'attribute' => 'dateStart',
                                'dateTime' => true,
                                'timeZone' => $timeZone,
                            ])
                        )->getRaw(),
                        $converter->convert(
                            $queryBuilder,
                            WhereItem::fromRaw([
                                'type' => 'before',
                                'attribute' => 'dateStart',
                                'value' => $beforeString,
                            ])
                        )->getRaw(),
                    ]
                ],
            ],
        ]);
    }

    /**
     * @throws BadRequest
     */
    private function apply(
        string $entityType,
        User $user,
        SelectBuilder $queryBuilder,
        string $beforeString
    ): void {

        if ($entityType === Task::ENTITY_TYPE) {
            $this->applyTask($user, $queryBuilder, $beforeString);

            return;
        }

        $converter = $this->whereConverterFactory->create($entityType, $user);
        $timeZone = $this->getUserTimeZone($user);

        $queryBuilder->where([
            'OR' => [
                $converter->convert(
                    $queryBuilder,
                    WhereItem::fromRaw([
                        'type' => 'today',
                        'attribute' => 'dateStart',
                        'dateTime' => true,
                        'timeZone' => $timeZone,
                    ])
                )->getRaw(),
                [
                    $converter->convert(
                        $queryBuilder,
                        WhereItem::fromRaw([
                            'type' => 'future',
                            'attribute' => 'dateEnd',
                            'dateTime' => true,
                            'timeZone' => $timeZone,
                        ])
                    )->getRaw(),
                    $converter->convert(
                        $queryBuilder,
                        WhereItem::fromRaw([
                            'type' => 'before',
                            'attribute' => 'dateStart',
                            'value' => $beforeString,
                        ])
                    )->getRaw(),
                ],
            ],
        ]);
    }
}
