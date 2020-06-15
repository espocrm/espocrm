<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\Error;

class Layout extends \Espo\Core\Services\Base
{
    protected function init()
    {
        $this->addDependency('acl');
        $this->addDependency('layout');
        $this->addDependency('entityManager');
        $this->addDependency('metadata');
        $this->addDependency('dataManager');
        $this->addDependency('user');
    }

    protected function getAcl()
    {
        return $this->getInjection('acl');
    }

    protected function getMetadata()
    {
        return $this->getInjection('metadata');
    }

    public function getOriginal(string $scope, string $name, ?string $setId = null)
    {
        $result = null;

        if ($setId) {
            $layout = $this->getRecordFromSet($scope, $name, $setId, true);
            if ($layout) {
                $result = $layout->get('data');
            }
        }

        if (!$result) {
            $result = $this->getInjection('layout')->get($scope, $name);
            $result = json_decode($result);
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
        $layoutSetId = null;
        $data = null;

        $em = $this->getInjection('entityManager');
        $user = $this->getInjection('user');

        if ($user->isPortal()) {
            $portalId = $user->get('portalId');
            if ($portalId) {
                $portal = $em->getRepository('Portal')->select(['layoutSetId'])->where(['id' => $portalId])->findOne();
                if ($portal) {
                    $layoutSetId = $portal->get('layoutSetId');
                }
            }
        } else {
            $teamId = $user->get('defaultTeamId');
            if ($teamId) {
                $team = $em->getRepository('Team')->select(['layoutSetId'])->where(['id' => $teamId])->findOne();
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
            $dataString = $this->getInjection('layout')->get($scope, $name);
            $data = json_decode($dataString);
        } else {
            $dataString = json_encode($data);
        }

        if (is_null($data)) {
            throw new NotFound("Layout {$scope}:{$name} is not found.");
        }

        if (!$this->getUser()->isAdmin()) {
            if ($name === 'relationships') {
                if (is_array($data)) {
                    foreach ($data as $i => $item) {
                        $link = $item;
                        if (is_object($item)) $link = $item->name ?? null;
                        $foreignEntityType = $this->getMetadata()->get(['entityDefs', $scope, 'links', $link, 'entity']);
                        if ($foreignEntityType) {
                            if (!$this->getAcl()->check($foreignEntityType)) {
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
        $em = $this->getInjection('entityManager');
        $layoutSet = $em->getEntity('LayoutSet', $setId);
        if (!$layoutSet) throw new NotFound("LayoutSet {$setId} not found.");

        $layoutList = $layoutSet->get('layoutList') ?? [];

        $fullName = $scope . '.' . $name;

        if (!in_array($fullName, $layoutList)) {
            if ($skipCheck) return null;
            throw new NotFound("Layout {$fullName} is no allowed in set.");
        }

        $layout = $em->getRepository('LayoutRecord')->where([
            'layoutSetId' => $setId,
            'name' => $fullName,
        ])->findOne();

        return $layout;
    }

    public function update(string $scope, string $name, ?string $setId, $data)
    {
        if ($setId) {
            $layout = $this->getRecordFromSet($scope, $name, $setId);

            $em = $this->getInjection('entityManager');

            if (!$layout) {
                $layout = $em->getEntity('LayoutRecord');
                $layout->set([
                    'layoutSetId' => $setId,
                    'name' => $scope . '.' . $name,
                ]);
            }

            $layout->set('data', $data);

            $em->saveEntity($layout);

            return $layout->get('data');
        }

        $layoutManager = $this->getInjection('layout');

        $layoutManager->set($data, $scope, $name);
        $result = $layoutManager->save();

        if ($result === false) throw new Error("Error while saving layout.");

        $this->getInjection('dataManager')->updateCacheTimestamp();

        return $layoutManager->get($scope, $name);
    }

    public function resetToDefault(string $scope, string $name, ?string $setId = null)
    {
        $this->getInjection('dataManager')->updateCacheTimestamp();

        if ($setId) {
            $layout = $this->getRecordFromSet($scope, $name, $setId);
            if ($layout) {
                $em = $this->getInjection('entityManager');
                $em->removeEntity($layout);
            }
            return $this->getOriginal($scope, $name);
        }

        $this->getInjection('layout')->resetToDefault($scope, $name);

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
            if (!is_object($item)) continue;

            $item = clone $item;
            $item->index = 5 + 0.001 * $i;

            if (!isset($item->name)) continue;

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
        $this->getInjection('layout')->resetToDefault($scope, 'relationships');
    }
}
