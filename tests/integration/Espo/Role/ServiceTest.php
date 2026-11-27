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

namespace tests\integration\Espo\Role;

use Espo\Core\Acl\Table;
use Espo\Core\Portal\Acl\Table as TablePortal;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Record\CreateParams;
use Espo\Core\Record\DeleteParams;
use Espo\Core\Record\Service;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Record\UpdateParams;
use Espo\Entities\Portal;
use Espo\Entities\PortalRole;
use Espo\Entities\Role;
use Espo\Entities\Team;
use Espo\Modules\Crm\Entities\Account;
use tests\integration\Core\BaseTestCase;

class ServiceTest extends BaseTestCase
{
    public function testCreateUpdate(): void
    {
        $service = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Role::class);

        $data = (object) [
            Account::ENTITY_TYPE => (object) [
                Table::ACTION_CREATE => Table::LEVEL_YES,
                Table::ACTION_READ => Table::LEVEL_ALL,
                Table::ACTION_EDIT => Table::LEVEL_OWN,
                Table::ACTION_DELETE => Table::LEVEL_NO,
                Table::ACTION_STREAM => Table::LEVEL_TEAM,
            ],
            'Activities' => true,
            'ExternalAccount' => false,
        ];

        $fieldData = (object) [
            Account::ENTITY_TYPE => (object) [
                'description' => (object) [
                    Table::ACTION_READ => Table::LEVEL_YES,
                    Table::ACTION_EDIT => Table::LEVEL_NO,
                ],
            ],
        ];

        /** @noinspection PhpUnhandledExceptionInspection */
        $role = $service->create((object) [
            'name' => 'Test',
            'data' => $data,
            'fieldData' => $fieldData,
        ], CreateParams::create())->getEntity();

        $this->assertEquals($data, $role->get('data'));
        $this->assertEquals($fieldData, $role->get('fieldData'));

        /** @noinspection PhpUnhandledExceptionInspection */
        $role = $service->update($role->getId(), (object) [
            'data' => (object) [],
            'fieldData' => (object) [],
        ], UpdateParams::create())->getEntity();

        $this->assertEquals((object) [], $role->get('data'));
        $this->assertEquals((object) [], $role->get('fieldData'));

        /** @noinspection PhpUnhandledExceptionInspection */
        $role = $service->update($role->getId(), (object) [
            'data' => $data,
            'fieldData' => $fieldData,
        ], UpdateParams::create())->getEntity();

        $this->assertEquals($data, $role->get('data'));
        $this->assertEquals($fieldData, $role->get('fieldData'));
    }

    public function testPortalCreateUpdate(): void
    {
        $service = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(PortalRole::class);

        $data = (object) [
            Account::ENTITY_TYPE => (object) [
                Table::ACTION_CREATE => Table::LEVEL_YES,
                Table::ACTION_READ => Table::LEVEL_ALL,
                Table::ACTION_EDIT => TablePortal::LEVEL_ACCOUNT,
                Table::ACTION_DELETE => Table::LEVEL_NO,
                Table::ACTION_STREAM => TablePortal::LEVEL_ACCOUNT,
            ],
            'Activities' => true,
            'ExternalAccount' => false,
        ];

        $fieldData = (object) [
            Account::ENTITY_TYPE => (object) [
                'description' => (object) [
                    Table::ACTION_READ => Table::LEVEL_YES,
                    Table::ACTION_EDIT => Table::LEVEL_NO,
                ],
            ],
        ];

        /** @noinspection PhpUnhandledExceptionInspection */
        $role = $service->create((object) [
            'name' => 'Test',
            'data' => $data,
            'fieldData' => $fieldData,
        ], CreateParams::create())->getEntity();

        $this->assertEquals($data, $role->get('data'));
        $this->assertEquals($fieldData, $role->get('fieldData'));

        /** @noinspection PhpUnhandledExceptionInspection */
        $role = $service->update($role->getId(), (object) [
            'data' => (object) [],
            'fieldData' => (object) [],
        ], UpdateParams::create())->getEntity();

        $this->assertEquals((object) [], $role->get('data'));
        $this->assertEquals((object) [], $role->get('fieldData'));

        /** @noinspection PhpUnhandledExceptionInspection */
        $role = $service->update($role->getId(), (object) [
            'data' => $data,
            'fieldData' => $fieldData,
        ], UpdateParams::create())->getEntity();

        $this->assertEquals($data, $role->get('data'));
        $this->assertEquals($fieldData, $role->get('fieldData'));
    }

    public function testDeleteReferenced(): void
    {
        $em = $this->getEntityManager();

        $service = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Role::class);

        $role = $em->createEntity(Role::ENTITY_TYPE, ['name' => 'Test']);
        $user = $this->createUser('test');
        $team = $em->createEntity(Team::ENTITY_TYPE, ['name' => 'Test']);

        // User.

        $em->getRelation($role, Role::LINK_USERS)->relate($user);

        $this->assertDeleteConflict($service, $role->getId(), 'cannotRemoveUsedByUsers', Role::ENTITY_TYPE);

        $em->getRelation($role, Role::LINK_USERS)->unrelate($user);

        // Team.

        $em->getRelation($role, Role::LINK_TEAMS)->relate($team);

        $this->assertDeleteConflict($service, $role->getId(), 'cannotRemoveUsedByTeams', Role::ENTITY_TYPE);

        $em->getRelation($role, Role::LINK_TEAMS)->unrelate($team);

        // Baseline.

        $this->setConfigParams(['baselineRoleId' => $role->getId()]);

        $this->assertDeleteConflict($service, $role->getId(), 'cannotRemoveBaseline', Role::ENTITY_TYPE);

        $this->setConfigParams(['baselineRoleId' => null]);

        // Not referenced.

        /** @noinspection PhpUnhandledExceptionInspection */
        $service->delete($role->getId(), DeleteParams::create());

        $this->assertNull($em->getEntityById(Role::ENTITY_TYPE, $role->getId()));
    }

    public function testPortalDeleteReferenced(): void
    {
        $em = $this->getEntityManager();

        $service = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(PortalRole::class);

        $role = $em->createEntity(PortalRole::ENTITY_TYPE, ['name' => 'Test']);
        $portal = $em->createEntity(Portal::ENTITY_TYPE, ['name' => 'Test']);

        // Portal.

        $em->getRelation($role, PortalRole::LINK_PORTALS)->relate($portal);

        $this->assertDeleteConflict(
            $service,
            $role->getId(),
            'cannotRemoveUsedByPortals',
            PortalRole::ENTITY_TYPE
        );

        $em->getRelation($role, PortalRole::LINK_PORTALS)->unrelate($portal);

        // Not referenced.

        /** @noinspection PhpUnhandledExceptionInspection */
        $service->delete($role->getId(), DeleteParams::create());

        $this->assertNull($em->getEntityById(PortalRole::ENTITY_TYPE, $role->getId()));
    }

    /**
     * @param Service<Role|PortalRole> $service
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function assertDeleteConflict(Service $service, string $id, string $label, string $scope): void
    {
        $exception = null;

        try {
            $service->delete($id, DeleteParams::create());
        } catch (Conflict $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);

        $body = json_decode($exception->getBody() ?? '');

        $this->assertEquals($label, $body->messageTranslation->label ?? null);
        $this->assertEquals($scope, $body->messageTranslation->scope ?? null);
    }
}
