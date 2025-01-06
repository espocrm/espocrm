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

namespace Espo\Classes\AppParams;

use Espo\Entities\Extension;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\Tools\App\AppParam;
use stdClass;

class Extensions implements AppParam
{
    private User $user;
    private EntityManager $entityManager;

    public function __construct(
        User $user,
        EntityManager $entityManager
    ) {
        $this->user = $user;
        $this->entityManager = $entityManager;
    }

    /**
     * @return stdClass[]
     */
    public function get(): array
    {
        if (!$this->user->isRegular() && !$this->user->isAdmin()) {
            return [];
        }

        $extensionList = $this->entityManager
            ->getRDBRepositoryByClass(Extension::class)
            ->where([
                'licenseStatus' => [
                    Extension::LICENSE_STATUS_INVALID,
                    Extension::LICENSE_STATUS_EXPIRED,
                    Extension::LICENSE_STATUS_SOFT_EXPIRED,
                ],
            ])
            ->find();

        $list = [];

        foreach ($extensionList as $extension) {
            $list[] = (object) [
                'name' => $extension->getName(),
                'version' => $extension->getVersion(),
                'licenseStatus' => $extension->getLicenseStatus(),
                'licenseStatusMessage' => $extension->getLicenseStatusMessage(),
                'isInstalled' => $extension->isInstalled(),
                'notify' => in_array(
                    $extension->getLicenseStatus(),
                    [
                        Extension::LICENSE_STATUS_INVALID,
                        Extension::LICENSE_STATUS_EXPIRED,
                    ]
                )
            ];
        }

        return $list;
    }
}
