<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Tools\Layout;

use Espo\Core\Acl;
use Espo\Core\Acl\Exceptions\NotImplemented;
use Espo\Core\DataManager;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Metadata;
use Espo\Entities\LayoutSet;
use Espo\Entities\Portal;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\Entities\LayoutRecord;
use Espo\Tools\LayoutManager\LayoutManager;

use stdClass;

class Service
{
    public function __construct(
        private Acl $acl,
        private LayoutProvider $layoutProvider,
        private LayoutManager $layoutManager,
        private EntityManager $entityManager,
        private Metadata $metadata,
        private DataManager $dataManager,
        private User $user
    ) {}

    /**
     * @return array<int, mixed>|stdClass|null
     * @throws NotFound
     * @throws Error
     */
    public function getOriginal(string $scope, string $name, ?string $setId = null)
    {
        $result = null;

        if ($setId) {
            $layout = $this->getRecordFromSet($scope, $name, $setId, true);

            if ($layout && $layout->get('data') !== null) {
                /** @var string $data */
                $data = $layout->get('data');

                $result = Json::decode($data);
            }
        }

        if (!$result) {
            $data = $this->layoutProvider->get($scope, $name) ?? 'false';

            $result = Json::decode($data);
        }

        if ($result === false) {
            if ($name === 'bottomPanelsDetail') {
                return $this->getOriginalBottomPanelsDetail($scope);
            }
        }

        return $result;
    }

    /**
     * @return mixed
     * @throws Forbidden
     * @throws NotFound
     * @throws Error
     */
    public function getForFrontend(string $scope, string $name)
    {
        try {
            if (!$this->acl->checkScope($scope)) {
                throw new Forbidden();
            }
        }
        catch (NotImplemented) {}

        $layoutSetId = null;
        $data = null;

        $em = $this->entityManager;
        $user = $this->user;

        if ($user->isPortal()) {
            $portalId = $user->get('portalId');

            if ($portalId) {
                $portal = $em
                    ->getRDBRepositoryByClass(Portal::class)
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
                    ->getRDBRepositoryByClass(Team::class)
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
                if (str_ends_with($name, 'Portal')) {
                    $nameReal = substr($name, 0, -6);
                }
            }

            $layout = $this->getRecordFromSet($scope, $nameReal, $layoutSetId, true);

            $data = $layout?->get('data');
        }

        if (!$data) {
            $dataString = $this->layoutProvider->get($scope, $name) ?? 'null';

            $data = Json::decode($dataString);
        }

        if (is_null($data)) {
            throw new NotFound("Layout $scope:$name is not found.");
        }

        if (!$this->user->isAdmin()) {
            if ($name === 'relationships') {
                if (is_array($data)) {
                    foreach ($data as $i => $item) {
                        $link = $item;

                        if (is_object($item)) {
                            /** @var stdClass $item */
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
            if ($name === 'bottomPanelsDetail') {
                return $this->getForFrontendBottomPanelsDetail($scope);
            }
        }

        return $data;
    }

    /**
     * @throws NotFound
     */
    protected function getRecordFromSet(
        string $scope,
        string $name,
        string $setId,
        bool $skipCheck = false
    ): ?LayoutRecord {

        $entityManager = $this->entityManager;

        $layoutSet = $entityManager->getEntityById(LayoutSet::ENTITY_TYPE, $setId);

        if (!$layoutSet) {
            throw new NotFound("LayoutSet $setId not found.");
        }

        $layoutList = $layoutSet->get('layoutList') ?? [];

        $fullName = $scope . '.' . $name;

        if (!in_array($fullName, $layoutList)) {
            if ($skipCheck) {
                return null;
            }

            throw new NotFound("Layout $fullName is no allowed in set.");
        }

        return $entityManager
            ->getRDBRepositoryByClass(LayoutRecord::class)
            ->where([
                'layoutSetId' => $setId,
                'name' => $fullName,
            ])
            ->findOne();
    }

    /**
     * @param mixed $data
     * @return mixed
     * @throws NotFound
     * @throws Error
     */
    public function update(string $scope, string $name, ?string $setId, $data)
    {
        if ($setId) {
            $layout = $this->getRecordFromSet($scope, $name, $setId);

            if (!$layout) {
                $layout = $this->entityManager->getNewEntity(LayoutRecord::ENTITY_TYPE);

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

    /**
     * @return array<int, mixed>|stdClass|null
     * @throws NotFound
     * @throws Error
     */
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

        if ($name === 'bottomPanelsDetail') {
            $this->resetToDefaultBottomPanelsDetail($scope);
        }

        return $this->getOriginal($scope, $name);
    }

    /**
     * @throws Error
     * @throws NotFound
     */
    private function getOriginalBottomPanelsDetail(string $scope): stdClass
    {
        $relationships = $this->getOriginal($scope, 'relationships') ?? [];

        if (!is_array($relationships)) {
            throw new Error("Bad 'relationships' layout.");
        }

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

            /** @var stdClass $item */

            $item = clone $item;
            $item->index = 5 + 0.001 * $i;

            if (!isset($item->name)) {
                continue;
            }

            $result->{$item->name} = $item;
        }

        return $result;
    }

    /**
     * @throws NotFound
     * @throws Error
     */
    private function getForFrontendBottomPanelsDetail(string $scope): stdClass
    {
        return $this->getOriginalBottomPanelsDetail($scope);
    }

    private function resetToDefaultBottomPanelsDetail(string $scope): void
    {
        $this->layoutManager->resetToDefault($scope, 'relationships');
    }
}
