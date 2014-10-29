<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 ************************************************************************/
namespace Espo\Core;

use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Util;
use Espo\Entities\User;

class SelectManagerFactory
{

    private $entityManager;

    private $user;

    private $acl;

    /**
     * @var Metadata
     * @since 1.0
     */
    private $metadata;

    public function __construct($entityManager, User $user, Acl $acl, $metadata)
    {
        $this->entityManager = $entityManager;
        $this->user = $user;
        $this->acl = $acl;
        $this->metadata = $metadata;
    }

    public function create($entityName)
    {
        /**
         * @var SelectManagers\Base $selectManager
         */
        $className = '\\Espo\\Custom\\SelectManagers\\' . Util::normilizeClassName($entityName);
        if (!class_exists($className)) {
            $moduleName = $this->metadata->getScopeModuleName($entityName);
            if ($moduleName) {
                $className = '\\Espo\\Modules\\' . $moduleName . '\\SelectManagers\\' . Util::normilizeClassName($entityName);
            } else {
                $className = '\\Espo\\SelectManagers\\' . Util::normilizeClassName($entityName);
            }
            if (!class_exists($className)) {
                $className = '\\Espo\\Core\\SelectManagers\\Base';
            }
        }
        $selectManager = new $className($this->entityManager, $this->user, $this->acl, $this->metadata);
        $selectManager->setEntityName($entityName);
        return $selectManager;
    }
}

