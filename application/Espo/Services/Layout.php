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

namespace Espo\Services;

use Espo\Core\{
    Exceptions\NotFound,
    Exceptions\Forbidden,
    Acl,
    Acl\Exceptions\NotImplemented,
    Utils\Layout as LayoutUtil,
    ORM\EntityManager,
    Utils\Metadata,
    DataManager,
    Utils\Json,
};

use Espo\{
    Entities\User,
    Tools\LayoutManager\LayoutManager,
};

class Layout
{
    /**
     * @var Acl
     */
    protected $acl;

    /**
     * @var LayoutUtil
     */
    protected $layout;

    /**
     * @var LayoutManager
     */
    protected $layoutManager;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * @var DataManager
     */
    protected $dataManager;

    /**
     * @var User
     */
    protected $user;

    public function __construct(
        Acl $acl,
        LayoutUtil $layout,
        LayoutManager $layoutManager,
        EntityManager $entityManager,
        Metadata $metadata,
        DataManager $dataManager,
        User $user
    ) {
        $this->acl = $acl;
        $this->layout = $layout;
        $this->layoutManager = $layoutManager;
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->dataManager = $dataManager;
        $this->user = $user;
    }

    /**
     * @return array|object|null
     */
    public function getOriginal(string $scope, string $name, ?string $setId = null)
    {
        $result = null;

        if ($setId) {
            $layout = $this->getRecordFromSet($scope, $name, $setId, true);

            if ($layout && $layout->get('data') !== null) {
                $result = Json::decode($layout->get('data'));
            }
        }

        if (!$result) {
            $result = Json::decode(
                $this->layout->get($scope, $name)
            );
        }

        if ($result === false) {
            $methodName = 'getOriginal' . ucfirst($name);

            if (method_exists($this, $methodName)) {
                $result = $this->$methodName($scope, $setId);
            }
        }

        return $result;
    }

    public function getForFrontend(string $scope, string $name)
    {
        try {
            if (!$this->acl->checkScope($scope)) {
                throw new Forbidden();
            }
        }
        catch (NotImplemented $e) {}

        $layoutSetId = null;
        $data = null;

        $em = $this->entityManager;
        $user = $this->user;

        if ($user->isPortal()) {
            $portalId = $user->get('portalId');

            if ($portalId) {
                $portal = $em
                    ->getRDBRepository('Portal')
                    ->select(['layoutSetId'])
                    ->where(['id' => $portalId])
                    ->findOne();

                if ($portal) {
                    $layoutSetId = $portal->get('layoutSetId');
                }
            }
        } else {
            $teamId = $user->get('defaultTeamId');

            if ($teamId) {
                $team = $em
                    ->getRDBRepository('Team')
                    ->select(['layoutSetId'])
                    ->where(['id' => $teamId])
                    ->findOne();

                if ($team) {
                    $layoutSetId = $team->get('layoutSetId');
                }
            }
        }

        if ($layoutSetId) {
            $nameReal = $name;

            if ($user->isPortal()) {
                if (substr($name, -6) === 'Portal') {
                    $nameReal = substr($name, 0, -6);
                }
            }

            $layout = $this->getRecordFromSet($scope, $nameReal, $layoutSetId, true);

            if ($layout) {
                $data = $layout->get('data');
            }
        }

        if (!$data) {
            $dataString = $this->layout->get($scope, $name);

            $data = json_decode($dataString);
        }
        else {
            $dataString = json_encode($data);
        }

        if (is_null($data)) {
            throw new NotFound("Layout {$scope}:{$name} is not found.");
        }

        if (!$this->user->isAdmin()) {
            if ($name === 'relationships') {
                if (is_array($data)) {
                    foreach ($data as $i => $item) {
                        $link = $item;

                        if (is_object($item)) {
                            $link = $item->name ?? null;
                        }

                        $foreignEntityType = $this->metadata
                            ->get(['entityDefs', $scope, 'links', $link, 'entity']);

                        if ($foreignEntityType) {
                            if (!$this->acl->tryCheck($foreignEntityType)) {
                                unset($data[$i]);
                            }
                        }
                    }

                    $data = array_values($data);
                }
            }
        }

        if ($data === false) {
            $methodName = 'getForFrontend' . ucfirst($name);

            if (method_exists($this, $methodName)) {
                $data = $this->$methodName($scope);
            }
        }

        return $data;
    }

    protected function getRecordFromSet(string $scope, string $name, string $setId, bool $skipCheck = false)
    {
        $em = $this->entityManager;

        $layoutSet = $em->getEntity('LayoutSet', $setId);

        if (!$layoutSet) {
            throw new NotFound("LayoutSet {$setId} not found.");
        }

        $layoutList = $layoutSet->get('layoutList') ?? [];

        $fullName = $scope . '.' . $name;

        if (!in_array($fullName, $layoutList)) {
            if ($skipCheck) {
                return null;
            }

            throw new NotFound("Layout {$fullName} is no allowed in set.");
        }

        $layout = $em
            ->getRDBRepository('LayoutRecord')
            ->where([
                'layoutSetId' => $setId,
                'name' => $fullName,
            ])
            ->findOne();

        return $layout;
    }

    public function update(string $scope, string $name, ?string $setId, $data)
    {
        if ($setId) {
            $layout = $this->getRecordFromSet($scope, $name, $setId);

            if (!$layout) {
                $layout = $this->entityManager->getEntity('LayoutRecord');

                $layout->set([
                    'layoutSetId' => $setId,
                    'name' => $scope . '.' . $name,
                ]);
            }

            $layout->set('data', Json::encode($data));

            $this->entityManager->saveEntity($layout);

            return Json::decode(
                $layout->get('data')
            );
        }

        $layoutManager = $this->layoutManager;

        $layoutManager->set($data, $scope, $name);

        $layoutManager->save();

        $this->dataManager->updateCacheTimestamp();

        return $layoutManager->get($scope, $name);
    }

    public function resetToDefault(string $scope, string $name, ?string $setId = null)
    {
        $this->dataManager->updateCacheTimestamp();

        if ($setId) {
            $layout = $this->getRecordFromSet($scope, $name, $setId);

            if ($layout) {
                $em = $this->entityManager;
                $em->removeEntity($layout);
            }

            return $this->getOriginal($scope, $name);
        }

        $this->layoutManager->resetToDefault($scope, $name);

        $methodName = 'resetToDefault' . ucfirst($name);

        if (method_exists($this, $methodName)) {
            $this->$methodName($scope);
        }

        return $this->getOriginal($scope, $name);
    }

    protected function getOriginalBottomPanelsDetail(string $scope, ?string $setId = null)
    {
        $relationships = $this->getOriginal($scope, 'relationships') ?? [];

        $result = (object) [];

        foreach ($relationships as $i => $item) {
            if (is_string($item)) {
                $item = (object) [
                    'name' => $item,
                ];
            }

            if (!is_object($item)) {
                continue;
            }

            $item = clone $item;
            $item->index = 5 + 0.001 * $i;

            if (!isset($item->name)) {
                continue;
            }

            $result->{$item->name} = $item;
        }

        return $result;
    }

    protected function getForFrontendBottomPanelsDetail(string $scope)
    {
        return $this->getOriginalBottomPanelsDetail($scope);
    }

    protected function resetToDefaultBottomPanelsDetail(string $scope)
    {
        $this->layoutManager->resetToDefault($scope, 'relationships');
    }
}
