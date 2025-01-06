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
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Name\Attribute;
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
    public function getOriginal(string $scope, string $name, ?string $setId = null): mixed
    {
        $result = null;

        if ($setId) {
            $layout = $this->getRecordFromSet($scope, $name, $setId, true);

            if ($layout && $layout->getData() !== null) {
                /** @var string $data */
                $data = $layout->getData();

                $result = Json::decode($data);
            }
        }

        if (!$result) {
            $data = $this->layoutProvider->get($scope, $name) ?? 'false';

            $result = Json::decode($data);
        }

        if ($result === false && $name === 'bottomPanelsDetail') {
            return $this->getOriginalBottomPanelsDetail($scope);
        }

        return $result;
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     * @throws Error
     */
    public function getForFrontend(string $scope, string $name): mixed
    {
        try {
            if (!$this->acl->checkScope($scope)) {
                throw new Forbidden("No access to scope $scope.");
            }
        } catch (NotImplemented) {}

        $data = null;

        $layoutSetId = $this->getUserLayoutSetId();

        if ($layoutSetId) {
            $nameReal = $name;

            if (
                $this->user->isPortal() &&
                str_ends_with($name, 'Portal')
            ) {
                $nameReal = substr($name, 0, -6);
            }

            $layout = $this->getRecordFromSet($scope, $nameReal, $layoutSetId, true);

            $data = $layout?->getData();
        }

        if (!$data) {
            $dataString = $this->layoutProvider->get($scope, $name) ?? 'null';

            $data = Json::decode($dataString);
        }

        if (is_null($data)) {
            throw new NotFound("Layout $scope:$name is not found.");
        }

        if (
            !$this->user->isAdmin() &&
            $name === 'relationships' &&
            is_array($data)
        ) {
            foreach ($data as $i => $item) {
                $link = $item;

                if (is_object($item)) {
                    /** @var stdClass $item */
                    $link = $item->name ?? null;
                }

                $foreignEntityType = $this->metadata
                    ->get(['entityDefs', $scope, 'links', $link, RelationParam::ENTITY]);

                if (
                    $foreignEntityType &&
                    !$this->acl->tryCheck($foreignEntityType)
                ) {
                    unset($data[$i]);
                }
            }

            $data = array_values($data);
        }

        if ($data === false && $name === 'bottomPanelsDetail') {
            return $this->getForFrontendBottomPanelsDetail($scope);
        }

        return $data;
    }

    /**
     * @throws NotFound
     */
    private function getRecordFromSet(
        string $scope,
        string $name,
        string $setId,
        bool $skipCheck = false
    ): ?LayoutRecord {

        $layoutSet = $this->entityManager
            ->getRDBRepositoryByClass(LayoutSet::class)
            ->getById($setId);

        if (!$layoutSet) {
            throw new NotFound("LayoutSet $setId not found.");
        }

        $fullName = $scope . '.' . $name;

        if (!in_array($fullName, $layoutSet->getLayoutList())) {
            if ($skipCheck) {
                return null;
            }

            throw new NotFound("Layout $fullName is no allowed in set.");
        }

        return $this->entityManager
            ->getRDBRepositoryByClass(LayoutRecord::class)
            ->where([
                'layoutSetId' => $setId,
                'name' => $fullName,
            ])
            ->findOne();
    }

    /**
     * @throws NotFound
     * @throws Error
     * @throws Forbidden
     */
    public function update(string $scope, string $name, ?string $setId, mixed $data): mixed
    {
        if (!$this->isCustomizable($scope)) {
            throw new Forbidden("$scope is not customizable.");
        }

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
                $layout->getData()
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
    public function resetToDefault(string $scope, string $name, ?string $setId = null): mixed
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

    private function getUserLayoutSetId(): ?string
    {
        if ($this->user->isPortal()) {
            $portalId = $this->user->getPortalId();

            if (!$portalId) {
                return null;
            }

            $portal = $this->entityManager
                ->getRDBRepositoryByClass(Portal::class)
                ->select(['layoutSetId'])
                ->where([Attribute::ID => $portalId])
                ->findOne();

            if (!$portal) {
                return null;
            }

            return $portal->getLayoutSet()?->getId();
        }

        if ($this->user->getLayoutSet()) {
            return $this->user->getLayoutSet()->getId();
        }

        $teamId = $this->user->getDefaultTeam()?->getId();

        if (!$teamId) {
            return null;
        }

        $team = $this->entityManager
            ->getRDBRepositoryByClass(Team::class)
            ->select(['layoutSetId'])
            ->where([Attribute::ID => $teamId])
            ->findOne();

        if (!$team) {
            return null;
        }

        return $team->getLayoutSet()?->getId();
    }

    private function isCustomizable(string $scope): bool
    {
        if (!$this->metadata->get("scopes.$scope.customizable")) {
            return false;
        }

        if ($this->metadata->get("scopes.$scope.entityManager.layouts") === false) {
            return false;
        }

        return true;
    }
}
