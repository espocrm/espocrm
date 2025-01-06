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
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Note;
use Espo\ORM\EntityManager;

/**
 * @noinspection PhpUnused
 */
class Audit implements Cleanup
{
    private const PERIOD = '3 months';

    public function __construct(
        private Metadata $metadata,
        private EntityManager $entityManager,
        private Config $config
    ) {}

    public function process(): void
    {
        if (!$this->config->get('cleanupAudit')) {
            return;
        }

        $entityTypeList = $this->getEntityTypeList();

        foreach ($entityTypeList as $scope) {
            $this->processEntityType($scope);
        }
    }

    private function processEntityType(string $entityType): void
    {
        $query = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from(Note::ENTITY_TYPE)
            ->where([
                'parentType' => $entityType,
                'createdAt<' => $this->getBefore()->toString(),
                'type' => [Note::TYPE_UPDATE, Note::TYPE_STATUS],
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }

    /**
     * @return string[]
     */
    private function getEntityTypeList(): array
    {
        /** @var string[] $scopeList */
        $scopeList = array_keys($this->metadata->get(['scopes']) ?? []);

        $scopeList = array_filter($scopeList, function ($item) {
            return $this->metadata->get("scopes.$item.entity") &&
                !$this->metadata->get("scopes.$item.preserveAuditLog") &&
                !$this->metadata->get("scopes.$item.stream");
        });

        return array_values($scopeList);
    }

    private function getBefore(): DateTime
    {
        /** @var string $period */
        $period = $this->config->get('cleanupAuditPeriod') ?? self::PERIOD;

        return DateTime::createNow()->modify('-' . $period);
    }
}
