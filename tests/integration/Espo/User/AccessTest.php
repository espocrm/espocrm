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

namespace tests\integration\Espo\User;

use Espo\Core\Container;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Formula\Exceptions\NotAllowedUsage;
use Espo\Core\Formula\Manager as FormulaManager;
use Espo\Core\Record\ServiceContainer;
use Espo\Entities\User;
use tests\integration\Core\BaseTestCase;

class AccessTest extends BaseTestCase
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testTypeChange(): void
    {
        $this->createUser([
            User::FIELD_USER_NAME => 'admin-test',
            User::ATTR_TYPE => User::TYPE_ADMIN,
        ]);

        $this->auth('admin-test');
        $this->reCreateApplication();

        $service = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(User::class);

        //

        $userRegular = $service->create((object) [
            'userName' => 'test-regular',
            'type' => User::TYPE_REGULAR
        ])->getEntity();

        //

        $thrown = false;

        try {
            $service->create((object) [
                'userName' => 'test',
                'type' => User::TYPE_SUPER_ADMIN,
            ]);
        } catch (Forbidden) {
            $thrown = true;
        }

        $this->assertTrue($thrown);

        //

        $service->update($userRegular->getId(), (object) [
            'type' => User::TYPE_ADMIN,
        ]);

        //

        $thrown = false;

        try {
            $service->update($userRegular->getId(), (object) [
                'type' => User::TYPE_SUPER_ADMIN,
            ]);
        } catch (Forbidden) {
            $thrown = true;
        }

        $this->assertTrue($thrown);

        //

        $fm = $this->getContainer()->getByClass(FormulaManager::class);

        $script = <<<'EOT'
            $data = object\create();
            $data['type'] = 'super-admin';

            record\update('User', '{{id}}', $data);
        EOT;

        $script = strtr($script, [
            '{{id}}' => $userRegular->getId(),
        ]);

        $thrown = false;

        try {
            $fm->run($script);
        } catch (NotAllowedUsage) {
            $thrown = true;
        }

        $this->assertTrue($thrown);
    }
}
