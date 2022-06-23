<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Record;

use Espo\Core\ServiceFactory as Factory;
use Espo\Core\Utils\Metadata;

use Espo\Entities\User;
use Espo\Core\Acl;
use Espo\Core\AclManager;

use RuntimeException;

/**
 * Create a service for a specific user.
 */
class ServiceFactory
{
    private const RECORD_SERVICE_NAME = 'Record';

    private const RECORD_TREE_SERVICE_NAME = 'RecordTree';

    /**
     * @var array<string,string>
     */
    private $defaultTypeMap = [
        'CategoryTree' => self::RECORD_TREE_SERVICE_NAME,
    ];

    private Factory $serviceFactory;

    private Metadata $metadata;

    private User $user;

    private Acl $acl;

    private AclManager $aclManager;

    public function __construct(
        Factory $serviceFactory,
        Metadata $metadata,
        User $user,
        Acl $acl,
        AclManager $aclManager
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->metadata = $metadata;
        $this->user = $user;
        $this->acl = $acl;
        $this->aclManager = $aclManager;
    }

    /**
     * @return Service<\Espo\ORM\Entity>
     */
    public function create(string $entityType): Service
    {
        $obj = $this->createInternal($entityType);

        $obj->setUser($this->user);
        $obj->setAcl($this->acl);

        return $obj;
    }

    /**
     * @return Service<\Espo\ORM\Entity>
     */
    public function createForUser(string $entityType, User $user): Service
    {
        $obj = $this->createInternal($entityType);

        $acl = $this->aclManager->createUserAcl($user);

        $obj->setUser($user);
        $obj->setAcl($acl);

        return $obj;
    }

    /**
     * @return Service<\Espo\ORM\Entity>
     */
    public function createInternal(string $entityType): Service
    {
        if (!$this->metadata->get(['scopes', $entityType, 'entity'])) {
            throw new RuntimeException("Can't create record service '{$entityType}', there's no such entity type.");
        }

        if (!$this->serviceFactory->checkExists($entityType)) {
            return $this->createDefault($entityType);
        }

        $service = $this->serviceFactory->createWith($entityType, ['entityType' => $entityType]);

        if (!$service instanceof Service) {
            return $this->createDefault($entityType);
        }

        return $service;
    }

    /**
     * @return Service<\Espo\ORM\Entity>
     */
    private function createDefault(string $entityType): Service
    {
        $default = self::RECORD_SERVICE_NAME;

        $type = $this->metadata->get(['scopes', $entityType, 'type']);

        if ($type) {
            $default = $this->defaultTypeMap[$type] ?? $default;
        }

        $obj = $this->serviceFactory->createWith($default, ['entityType' => $entityType]);

        if (!$obj instanceof Service) {
            throw new RuntimeException("Service class {$default} is not instance of Record.");
        }

        return $obj;
    }
}
