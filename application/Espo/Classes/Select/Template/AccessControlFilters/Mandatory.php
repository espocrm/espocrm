<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
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

namespace Espo\Classes\Select\Template\AccessControlFilters;

use Espo\ORM\{
    Query\SelectBuilder,
    Defs,
};

use Espo\Core\{
    Select\AccessControl\Filter,
    AclManager,
    Acl\Exceptions\NotImplemented,
};

use Espo\Entities\User;

class Mandatory implements Filter
{
    private $user;

    private $defs;

    private $aclManager;

    public function __construct(User $user, Defs $defs, AclManager $aclManager)
    {
        $this->user = $user;
        $this->defs = $defs;
        $this->aclManager = $aclManager;
    }

    public function apply(SelectBuilder $queryBuilder): void
    {
        if ($this->user->isAdmin()) {
            return;
        }

        $forbiddenEntityTypeList = [];

        foreach ($this->defs->getEntityTypeList() as $entityType) {
            try {
                if (!$this->aclManager->checkScope($this->user, $entityType)) {
                    $forbiddenEntityTypeList[] = $entityType;
                }
            }
            catch (NotImplemented $e) {}
        }

        if (empty($forbiddenEntityTypeList)) {
            return;
        }

        $queryBuilder->where([
            'entityType!=' => $forbiddenEntityTypeList,
        ]);
    }
}
