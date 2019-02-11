<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

namespace Espo\Core\Console\Commands;

class AclCheck extends Base
{
    public function run()
    {
        $userId = isset($_SERVER['argv'][2]) ? trim($_SERVER['argv'][2]) : null;
        if (empty($userId)) return;

        $scope = isset($_SERVER['argv'][3]) ? trim($_SERVER['argv'][3]) : null;
        if (empty($scope)) return;

        $id = isset($_SERVER['argv'][4]) ? trim($_SERVER['argv'][4]) : null;
        if (empty($id)) return;

        $action = isset($_SERVER['argv'][5]) ? trim($_SERVER['argv'][5]) : null;

        $portalId = isset($_SERVER['argv'][6]) ? trim($_SERVER['argv'][6]) : null;

        $container = $this->getContainer();

        if ($portalId) {
            $application = new \Espo\Core\Portal\Application($portalId);
            $container = $application->getContainer();
        }

        $entityManager = $container->get('entityManager');

        $user = $entityManager->getEntity('User', $userId);
        if (!$user) return;

        if ($portalId) {
            if (
                !$user->isPortal()
                ||
                !in_array($portalId, $user->getLinkMultipleIdList('portals'))
            ) {
                return;
            }
        }

        $entity = $entityManager->getEntity($scope, $id);
        if (!$entity) return;

        $aclManager = $container->get('aclManager');

        if ($aclManager->check($user, $entity, $action)) {
            return 'true';
        }
    }
}
