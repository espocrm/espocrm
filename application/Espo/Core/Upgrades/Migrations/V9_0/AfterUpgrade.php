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

namespace Espo\Core\Upgrades\Migrations\V9_0;

use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Upgrades\Migration\Script;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Preferences;
use Espo\Entities\Role;
use Espo\Entities\ScheduledJob;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\UpdateBuilder;

class AfterUpgrade implements Script
{
    public function __construct(
        private EntityManager $entityManager,
        private Metadata $metadata,
        private Config $config,
        private Config\ConfigWriter $configWriter,
    ) {}

    public function run(): void
    {
        $this->updateRoles();
        $this->setReactionNotifications();
        $this->createScheduledJob();
        $this->setAclLinks();
        $this->fixTimezone();
    }

    private function updateRoles(): void
    {
        $query = UpdateBuilder::create()
            ->in(Role::ENTITY_TYPE)
            ->set(['userCalendarPermission' => Expression::column('userPermission')])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }

    private function createScheduledJob(): void
    {
        $found = $this->entityManager
            ->getRDBRepositoryByClass(ScheduledJob::class)
            ->where(['job' => 'SendScheduledEmails'])
            ->findOne();

        if ($found) {
            return;
        }

        $this->entityManager->createEntity(ScheduledJob::ENTITY_TYPE, [
            'name' => 'Send Scheduled Emails',
            'job' => 'SendScheduledEmails',
            'status' => ScheduledJob::STATUS_ACTIVE,
            'scheduling' => '*/10 * * * *',
        ], [SaveOption::SKIP_ALL => true]);
    }

    private function setReactionNotifications(): void
    {
        $users = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->sth()
            ->where([
                'isActive' => true,
                'type' => [
                    User::TYPE_ADMIN,
                    User::TYPE_REGULAR,
                    User::TYPE_PORTAL,
                ]
            ])
            ->find();

        foreach ($users as $user) {
            $preferences = $this->entityManager->getRepositoryByClass(Preferences::class)->getById($user->getId());

            if (!$preferences) {
                continue;
            }

            $preferences->set('reactionNotifications', true);
            $this->entityManager->saveEntity($preferences);
        }
    }

    private function setAclLinks(): void
    {
        /** @var array<string, array<string, mixed>> $scopes */
        $scopes = $this->metadata->get('scopes', []);

        foreach ($scopes as $scope => $defs) {
            if (($defs['entity'] ?? false) && ($defs['isCustom'] ?? false)) {
                $this->setAclLinksForEntityType($scope);
            }
        }
    }

    private function setAclLinksForEntityType(string $entityType): void
    {
        $relations = $this->entityManager
            ->getDefs()
            ->getEntity($entityType)
            ->getRelationList();

        $contactLink = null;
        $accountLink = null;

        foreach ($relations as $relation) {
            if (
                $relation->getName() === 'contact' &&
                $relation->tryGetForeignEntityType() === Contact::ENTITY_TYPE
            ) {
                $contactLink = $relation->getName();
            }
        }

        if (!$contactLink) {
            foreach ($relations as $relation) {
                if (
                    $relation->getName() === 'contacts' &&
                    $relation->tryGetForeignEntityType() === Contact::ENTITY_TYPE
                ) {
                    $contactLink = $relation->getName();
                }
            }
        }

        foreach ($relations as $relation) {
            if (
                $relation->getName() === 'account' &&
                $relation->tryGetForeignEntityType() === Account::ENTITY_TYPE
            ) {
                $accountLink = $relation->getName();
            }
        }

        if (!$accountLink) {
            foreach ($relations as $relation) {
                if (
                    $relation->getName() === 'accounts' &&
                    $relation->tryGetForeignEntityType() === Account::ENTITY_TYPE
                ) {
                    $accountLink = $relation->getName();
                }
            }
        }

        $this->metadata->set('aclDefs', $entityType, ['contactLink' => $contactLink]);
        $this->metadata->set('aclDefs', $entityType, ['accountLink' => $accountLink]);

        $this->metadata->save();
    }

    private function fixTimezone(): void
    {
        $map = [
            'Europe/Kiev' => 'Europe/Kyiv',
            'Europe/Uzhgorod' => 'Europe/Uzhhorod',
            'Europe/Zaporozhye' => 'Europe/Zaporozhye',
        ];

        $timeZone = $this->config->get('timeZone');

        if (in_array($timeZone, array_keys($map))) {
            $timeZone = $map[$timeZone];

            $this->configWriter->set('timeZone', $timeZone);
            $this->configWriter->save();
        }
    }
}
