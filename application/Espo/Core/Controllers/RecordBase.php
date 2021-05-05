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

use Espo\Core\Exceptions\{
    Forbidden,
    NotFound,
    BadRequest,
};

use Espo\Core\{
    Record\Collection as RecordCollection,
    Record\ServiceContainer as RecordServiceContainer,
    Record\SearchParamsFetcher,
    Container,
    Acl,
    AclManager,
    Utils\Config,
    Utils\Metadata,
    ServiceFactory,
    Api\Request,
    Api\Response,
    Record\Crud as CrudService,
    Di,
};


use Espo\Entities\{
    User,
    Preferences,
};

use StdClass;

class RecordBase extends Base implements Di\EntityManagerAware
{
    use Di\EntityManagerSetter;

    public static $defaultAction = 'list';

    /**
     * @var SearchParamsFetcher
     */
    protected $searchParamsFetcher;

    /**
     * @var RecordServiceContainer
     */
    protected $recordServiceContainer;

    protected $config;

    protected $user;

    protected $acl;

    /**
     * @deprecated
     */
    protected $entityManager;

    public function __construct(
        SearchParamsFetcher $searchParamsFetcher,
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

    protected function getRecordService(?string $entityType = null): CrudService
    {
        return $this->recordServiceContainer->get($entityType ?? $this->getEntityType());
    }

    /**
     * Read a record.
     */
    public function getActionRead(Request $request, Response $response): StdClass
    {
        if (method_exists($this, 'actionRead')) {
            // For backward compatibility.
            return (object) $this->actionRead($request->getRouteParams(), $request->getParsedBody(), $request);
        }

        $id = $request->getRouteParam('id');

        $entity = $this->getRecordService()->read($id);

        if (!$entity) {
            throw new NotFound();
        }

        return $entity->getValueMap();
    }

    /**
     * Create a record.
     */
    public function postActionCreate(Request $request, Response $response): StdClass
    {
        if (method_exists($this, 'actionCreate')) {
            // For backward compatibility.
            return (object) $this->actionCreate($request->getRouteParams(), $request->getParsedBody(), $request);
        }

        $data = $request->getParsedBody();

        $entity = $this->getRecordService()->create($data);

        return $entity->getValueMap();
    }

    public function patchActionUpdate(Request $request, Response $response): StdClass
    {
        return $this->putActionUpdate($request, $response);
    }

    /**
     * Update a record.
     */
    public function putActionUpdate(Request $request, Response $response): StdClass
    {
        if (method_exists($this, 'actionUpdate')) {
            // For backward compatibility.
            return (object) $this->actionUpdate($request->getRouteParams(), $request->getParsedBody(), $request);
        }

        $id = $request->getRouteParam('id');

        $data = $request->getParsedBody();

        $entity = $this->getRecordService()->update($id, $data);

        return $entity->getValueMap();
    }

    /**
     * List records.
     */
    public function getActionList(Request $request, Response $response): StdClass
    {
        if (method_exists($this, 'actionList')) {
            // For backward compatibility.
            return (object) $this->actionList($request->getRouteParams(), $request->getParsedBody(), $request);
        }

        $searchParams = $this->fetchSearchParamsFromRequest($request);

        $result = $this->getRecordService()->find($searchParams);

        if ($result instanceof RecordCollection) {
            return (object) [
                'total' => $result->getTotal(),
                'list' => $result->getValueMapList(),
            ];
        }

        if (is_array($result)) {
            return (object) [
                'total' => $result['total'],
                'list' => isset($result['collection']) ?
                    $result['collection']->getValueMapList() :
                    $result['list'],
            ];
        }

        return (object) [
            'total' => $result->total,
            'list' => isset($result->collection) ?
                $result->collection->getValueMapList() :
                $result->list,
        ];
    }

    /**
     * Delete a record.
     */
    public function deleteActionDelete(Request $request, Response $response): bool
    {
        if (method_exists($this, 'actionDelete')) {
            // For backward compatibility.
            return (object) $this->actionDelete($request->getRouteParams(), $request->getParsedBody(), $request);
        }

        $id = $request->getRouteParam('id');

        $this->getRecordService()->delete($id);

        return true;
    }

    protected function fetchSearchParamsFromRequest(Request $request): array
    {
        return $this->searchParamsFetcher->fetchRaw($request);
    }

    public function postActionExport(Request $request): StdClass
    {
        $data = $request->getParsedBody();

        if ($this->config->get('exportDisabled') && !$this->user->isAdmin()) {
            throw new Forbidden("Export is disabled.");
        }

        $ids = isset($data->ids) ?
            $data->ids : null;

        $where = isset($data->where) ?
            json_decode(json_encode($data->where), true) : null;

        $byWhere = isset($data->byWhere) ?
            $data->byWhere : false;

        $selectData = isset($data->selectData) ?
            json_decode(json_encode($data->selectData), true) : null;

        $actionParams = [];

        if ($byWhere) {
            $actionParams['selectData'] = $selectData;
            $actionParams['where'] = $where;
        }
        else {
            $actionParams['ids'] = $ids;
        }

        if (isset($data->attributeList)) {
            $actionParams['attributeList'] = $data->attributeList;
        }

        if (isset($data->fieldList)) {
            $actionParams['fieldList'] = $data->fieldList;
        }

        if (isset($data->format)) {
            $actionParams['format'] = $data->format;
        }

        return (object) [
            'id' => $this->getRecordService()->export($actionParams),
        ];
    }

    public function postActionGetDuplicateAttributes(Request $request): StdClass
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
