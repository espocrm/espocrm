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

namespace Espo\Core\Upgrades\Migrations\V8_3;

use Doctrine\DBAL\Exception as DbalException;
use Espo\Core\Templates\Entities\Event;
use Espo\Core\Upgrades\Migration\Script;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Database\Helper;
use Espo\Core\Utils\Metadata;
use Espo\Entities\AuthenticationProvider;
use Espo\Entities\Role;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\UpdateBuilder;

class AfterUpgrade implements Script
{
    public function __construct(
        private EntityManager $entityManager,
        private Metadata $metadata,
        private Config $config,
        private Helper $helper
    ) {}

    /**
     * @throws DbalException
     */
    public function run(): void
    {
        $this->updateRoles();
        $this->updateMetadata();
        $this->updateAuthenticationProviders();
        $this->renameSubscription();
    }

    private function updateRoles(): void
    {
        $query = UpdateBuilder::create()
            ->in(Role::ENTITY_TYPE)
            ->set(['mentionPermission' => Expression::column('assignmentPermission')])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }

    private function updateMetadata(): void
    {
        $defs = $this->metadata->get(['scopes']);

        foreach ($defs as $entityType => $item) {
            $isCustom = $item['isCustom'] ?? false;
            $type = $item['type'] ?? false;

            if (!$isCustom) {
                continue;
            }

            if ($type !== Event::TEMPLATE_TYPE) {
                continue;
            }

            $clientDefs = $this->metadata->getCustom('clientDefs', $entityType) ?? (object) [];

            $clientDefs->viewSetupHandlers ??= (object) [];

            $clientDefs->viewSetupHandlers->{'record/detail'} = [
                "__APPEND__",
                "crm:handlers/event/reminders-handler"
            ];

            $clientDefs->viewSetupHandlers->{'record/edit'} = [
                "__APPEND__",
                "crm:handlers/event/reminders-handler"
            ];

            if (isset($clientDefs->dynamicLogic->fields->reminders)) {
                unset($clientDefs->dynamicLogic->fields->reminders);
            }

            $this->metadata->saveCustom('clientDefs', $entityType, $clientDefs);
        }
    }

    private function updateAuthenticationProviders(): void
    {
        $collection = $this->entityManager->getRDBRepositoryByClass(AuthenticationProvider::class)
            ->where(['method' => 'Oidc'])
            ->find();

        foreach ($collection as $entity) {
            $entity->set('oidcAuthorizationPrompt', $this->config->get('oidcAuthorizationPrompt'));

            $this->entityManager->saveEntity($entity);
        }
    }

    /**
     * @throws DbalException
     */
    private function renameSubscription(): void
    {
        $connection = $this->helper->getDbalConnection();
        $schemaManager = $connection->createSchemaManager();

        if (!$schemaManager->tablesExist('subscription')) {
            return;
        }

        if ($schemaManager->tablesExist('stream_subscription')) {
            try {
                $schemaManager->dropTable('stream_subscription');
            } catch (DbalException) {
                $schemaManager->renameTable('stream_subscription', 'stream_subscription_waste');
            }
        }

        $schemaManager->renameTable('subscription', 'stream_subscription');
    }
}
