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

namespace Espo\Classes\Cleanup;

use Espo\Core\Cleanup\Cleanup;
use Espo\Core\Field\DateTime;
use Espo\Core\Name\Field;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\StreamSubscription;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Condition as Cond;

class Subscribers implements Cleanup
{
    private const PERIOD = '2 months';

    public function __construct(
        private Metadata $metadata,
        private EntityManager $entityManager,
        private Config $config
    ) {}

    public function process(): void
    {
        if (!$this->config->get('cleanupSubscribers')) {
            return;
        }

        /** @var string[] $scopeList */
        $scopeList = array_keys($this->metadata->get(['scopes']) ?? []);

        /** @var string[] $scopeList */
        $scopeList = array_values(array_filter(
            $scopeList,
            fn ($item) => (bool) $this->metadata->get(['scopes', $item, 'stream'])
        ));

        foreach ($scopeList as $scope) {
            $this->processEntityType($scope);
        }
    }

    private function processEntityType(string $entityType): void
    {
        /** @var ?array<string, mixed> $data */
        $data = $this->metadata->get(['streamDefs', $entityType, 'subscribersCleanup']);

        if (!($data['enabled'] ?? false)) {
            return;
        }

        /** @var string $dateField */
        $dateField = $data['dateField'] ?? Field::CREATED_AT;
        /** @var ?string[] $statusList */
        $statusList = $data['statusList'] ?? null;
        /** @var ?string $statusField */
        $statusField = $this->metadata->get(['scopes', $entityType, 'statusField']);

        if ($statusList === null || $statusField === null) {
            return;
        }

        /** @var string $period */
        $period = $this->metadata->get(['streamDefs', $entityType, 'subscribersCleanup', 'period']) ??
            $this->config->get('cleanupSubscribersPeriod') ??
            self::PERIOD;

        $before = DateTime::createNow()->modify('-' . $period);

        $query = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from(StreamSubscription::ENTITY_TYPE, 'subscription')
            ->join(
                $entityType,
                'entity',
                Cond::equal(
                    Cond::column('entity.id'),
                    Cond::column('entityId')
                )
            )
            ->where(
                Cond::and(
                    Cond::equal(
                        Cond::column('entityType'),
                        $entityType
                    ),
                    Cond::less(
                        Cond::column('entity.' . $dateField),
                        $before->toString()
                    ),
                    Cond::in(
                        Cond::column('entity.' . $statusField),
                        $statusList
                    )
                )
            )
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }
}
