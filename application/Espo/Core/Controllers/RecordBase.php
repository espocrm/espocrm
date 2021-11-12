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

namespace Espo\Core\Controllers;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;

use Espo\Core\Record\ServiceContainer as RecordServiceContainer;
use Espo\Core\Record\SearchParamsFetcher;
use Espo\Core\Record\CreateParamsFetcher;
use Espo\Core\Record\ReadParamsFetcher;
use Espo\Core\Record\UpdateParamsFetcher;
use Espo\Core\Record\DeleteParamsFetcher;
use Espo\Core\Record\Service as RecordService;

use Espo\Core\Container;
use Espo\Core\Acl;
use Espo\Core\AclManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Core\ServiceFactory;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Select\SearchParams;

use Espo\Core\Di;

use Espo\Entities\User;
use Espo\Entities\Preferences;

use Espo\ORM\EntityManager;

use stdClass;

class RecordBase extends Base implements Di\EntityManagerAware, Di\InjectableFactoryAware
{
    use Di\EntityManagerSetter;
    use Di\InjectableFactorySetter;

    public static $defaultAction = 'list';

    /**
     * @var SearchParamsFetcher
     */
    protected $searchParamsFetcher;

    /**
     * @var CreateParamsFetcher
     */
    protected $createParamsFetcher;

    /**
     * @var ReadParamsFetcher
     */
    protected $readParamsFetcher;

    /**
     * @var UpdateParamsFetcher
     */
    protected $updateParamsFetcher;

    /**
     * @var DeleteParamsFetcher
     */
    protected $deleteParamsFetcher;

    /**
     * @var RecordServiceContainer
     */
    protected $recordServiceContainer;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var Acl
     */
    protected $acl;

    /**
     * @deprecated
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(
        SearchParamsFetcher $searchParamsFetcher,
        CreateParamsFetcher $createParamsFetcher,
        ReadParamsFetcher $readParamsFetcher,
        UpdateParamsFetcher $updateParamsFetcher,
        DeleteParamsFetcher $deleteParamsFetcher,
        RecordServiceContainer $recordServiceContainer,
        Config $config,
        User $user,
        Acl $acl,
        Container $container, // for backward compatibility
        AclManager $aclManager, // for backward compatibility
        Preferences $preferences, // for backward compatibility
        Metadata $metadata, // for backward compatibility
        ServiceFactory $serviceFactory // for backward compatibility
    ) {
        $this->searchParamsFetcher = $searchParamsFetcher;
        $this->createParamsFetcher = $createParamsFetcher;
        $this->readParamsFetcher = $readParamsFetcher;
        $this->updateParamsFetcher = $updateParamsFetcher;
        $this->deleteParamsFetcher = $deleteParamsFetcher;
        $this->recordServiceContainer = $recordServiceContainer;
        $this->config = $config;
        $this->user = $user;
        $this->acl = $acl;

        parent::__construct(
            $container,
            $user,
            $acl,
            $aclManager,
            $config,
            $preferences,
            $metadata,
            $serviceFactory
        );
    }

    protected function getEntityType(): string
    {
        return $this->name;
    }

    protected function getRecordService(?string $entityType = null): RecordService
    {
        return $this->recordServiceContainer->get($entityType ?? $this->getEntityType());
    }

    /**
     * Read a record.
     */
    public function getActionRead(Request $request, Response $response): stdClass
    {
        if (method_exists($this, 'actionRead')) {
            // For backward compatibility.
            return (object) $this->actionRead($request->getRouteParams(), $request->getParsedBody(), $request);
        }

        $id = $request->getRouteParam('id');
        $params = $this->readParamsFetcher->fetch($request);

        $entity = $this->getRecordService()->read($id, $params);

        return $entity->getValueMap();
    }

    /**
     * Create a record.
     */
    public function postActionCreate(Request $request, Response $response): stdClass
    {
        if (method_exists($this, 'actionCreate')) {
            // For backward compatibility.
            return (object) $this->actionCreate($request->getRouteParams(), $request->getParsedBody(), $request);
        }

        $data = $request->getParsedBody();
        $params = $this->createParamsFetcher->fetch($request);

        $entity = $this->getRecordService()->create($data, $params);

        return $entity->getValueMap();
    }

    public function patchActionUpdate(Request $request, Response $response): stdClass
    {
        return $this->putActionUpdate($request, $response);
    }

    /**
     * Update a record.
     */
    public function putActionUpdate(Request $request, Response $response): stdClass
    {
        if (method_exists($this, 'actionUpdate')) {
            // For backward compatibility.
            return (object) $this->actionUpdate($request->getRouteParams(), $request->getParsedBody(), $request);
        }

        $id = $request->getRouteParam('id');
        $data = $request->getParsedBody();

        $params = $this->updateParamsFetcher->fetch($request);

        $entity = $this->getRecordService()->update($id, $data, $params);

        return $entity->getValueMap();
    }

    /**
     * List records.
     */
    public function getActionList(Request $request, Response $response): stdClass
    {
        if (method_exists($this, 'actionList')) {
            // For backward compatibility.
            return (object) $this->actionList($request->getRouteParams(), $request->getParsedBody(), $request);
        }

        $searchParams = $this->fetchSearchParamsFromRequest($request);

        $recordCollection = $this->getRecordService()->find($searchParams);

        return (object) [
            'total' => $recordCollection->getTotal(),
            'list' => $recordCollection->getValueMapList(),
        ];
    }

    /**
     * Delete a record.
     */
    public function deleteActionDelete(Request $request, Response $response): bool
    {
        if (method_exists($this, 'actionDelete')) {
            // For backward compatibility.
            return $this->actionDelete($request->getRouteParams(), $request->getParsedBody(), $request);
        }

        $id = $request->getRouteParam('id');
        $params = $this->deleteParamsFetcher->fetch($request);

        $this->getRecordService()->delete($id, $params);

        return true;
    }

    protected function fetchSearchParamsFromRequest(Request $request): SearchParams
    {
        return $this->searchParamsFetcher->fetch($request);
    }

    public function postActionGetDuplicateAttributes(Request $request): stdClass
    {
        $id = $request->getParsedBody()->id ?? null;

        if (!$id) {
            throw new BadRequest();
        }

        return $this->getRecordService()->getDuplicateAttributes($id);
    }

    public function postActionRestoreDeleted(Request $request): bool
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $id = $request->getParsedBody()->id ?? null;

        if (!$id) {
            throw new BadRequest();
        }

        $this->getRecordService()->restoreDeleted($id);

        return true;
    }

    /**
     * @deprecated
     */
    protected function getEntityManager()
    {
        return $this->entityManager;
    }
}
