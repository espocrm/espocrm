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

namespace Espo\Core\Rebuild\Actions;

use Espo\Core\Rebuild\RebuildAction;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Integration;
use Espo\ORM\EntityManager;

/**
 * @noinspection PhpUnused
 */
class SetIntegrationDefaults implements RebuildAction
{
    public function __construct(
        private Metadata $metadata,
        private EntityManager $entityManager,
    ) {}

    public function process(): void
    {
        /** @var string[] $integrations */
        $integrations = array_keys($this->metadata->get('integrations') ?? []);

        foreach ($integrations as $integration) {
            $this->processItem($integration);
        }
    }

    private function processItem(string $name): void
    {
        $integration = $this->entityManager
            ->getRDBRepositoryByClass(Integration::class)
            ->getById($name);

        if (!$integration || !$integration->isEnabled()) {
            return;
        }

        /** @var array<string, array<string, mixed>> $fields */
        $fields = $this->metadata->get("integrations.$name.fields") ?? [];

        foreach ($fields as $field => $defs) {
            $default = $defs['default'] ?? null;

            if ($default === null) {
                continue;
            }

            if ($integration->has($field)) {
                continue;
            }

            $integration->set($field, $default);
        }

        $this->entityManager->saveEntity($integration);
    }
}
