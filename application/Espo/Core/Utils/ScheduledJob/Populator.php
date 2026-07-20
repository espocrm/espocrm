<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Core\Utils\ScheduledJob;

use Espo\Core\Binding\Attributes\Qualify;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\Entities\ScheduledJob;
use Espo\ORM\EntityManager;

/**
 * @internal
 * @since 10.0.0
 */
class Populator
{
    public function __construct(
        private EntityManager $entityManager,
        private Metadata $metadata,
        #[Qualify(Language::QUALIFIER_DEFAULT)]
        private Language $defaultLanguage,
    ) {}

    /**
     * @return string[]
     */
    public function populate(): array
    {
        $output = [];

        /** @var array<string, array{isDefault?: bool, scheduling?: string}> $defs */
        $defs = $this->metadata->get('app.scheduledJobs', []);

        foreach ($defs as $name => $def) {
            $scheduling = $def['scheduling'] ?? null;
            $idDefault = $def['isDefault'] ?? false;

            if (!$idDefault || !$scheduling) {
                continue;
            }

            $created = $this->createIsNotExists($name, $scheduling);

            if ($created) {
                $output[] = $name;
            }
        }

        return $output;
    }

    private function createIsNotExists(string $name, string $scheduling): bool
    {
        if ($this->exists($name)) {
            return false;
        }

        $recordName = $this->defaultLanguage->translateOption($name, 'job', ScheduledJob::ENTITY_TYPE);

        $entity = $this->entityManager->getRDBRepositoryByClass(ScheduledJob::class)->getNew();

        $entity
            ->setJob($name)
            ->setActive()
            ->setScheduling($scheduling)
            ->setName($recordName);

        $this->entityManager->saveEntity($entity);

        return true;
    }

    private function exists(string $name): bool
    {
        $one = $this->entityManager->getRDBRepositoryByClass(ScheduledJob::class)
            ->where([
                ScheduledJob::FIELD_JOB => $name,
            ])
            ->findOne();

        return $one !== null;
    }
}
