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

use Espo\Core\Acl;
use Espo\Core\Utils\Metadata as MetadataUtil;

use Espo\Entities\User;

class Metadata
{
    private $acl;

    private $metadata;

    private $user;

    public function __construct(Acl $acl, MetadataUtil $metadata, User $user)
    {
        $this->acl = $acl;
        $this->metadata = $metadata;
        $this->user = $user;
    }

    public function getDataForFrontend()
    {
        $data = $this->metadata->getAllForFrontend();

        if ($this->user->isAdmin()) {
            return $data;
        }

        $scopeList = array_keys($this->metadata->get(['entityDefs'], []));

        foreach ($scopeList as $scope) {
            $isEntity = $this->metadata->get(['scopes', $scope, 'entity']);

            if ($isEntity === false) {
                continue;
            }

            if (in_array($scope, ['Reminder'])) {
                continue;
            }

            $isAllowed = $isEntity !== null && $this->acl->tryCheck($scope);

            if (!$isAllowed) {
                unset($data->entityDefs->$scope);
                unset($data->clientDefs->$scope);
                unset($data->entityAcl->$scope);
                unset($data->scopes->$scope);
            }
        }

        $entityTypeList = array_keys(get_object_vars($data->entityDefs));

        foreach ($entityTypeList as $entityType) {
            $linksDefs = $this->metadata->get(['entityDefs', $entityType, 'links'], []);

            $forbiddenFieldList = $this->acl->getScopeForbiddenFieldList($entityType);

            foreach ($linksDefs as $link => $defs) {
                $type = $defs['type'] ?? null;

                $hasField = (bool) $this->metadata->get(['entityDefs', $entityType, 'fields', $link]);

                if ($type === 'belongsToParent') {
                    if ($hasField) {
                        $parentEntityList = $this->metadata
                            ->get(['entityDefs', $entityType, 'fields', $link, 'entityList']);

                        if (is_array($parentEntityList)) {
                            foreach ($parentEntityList as $i => $e) {
                                if (!$this->acl->tryCheck($e)) {
                                    unset($parentEntityList[$i]);
                                }
                            }

                            $parentEntityList = array_values($parentEntityList);

                            $data->entityDefs->$entityType->fields->$link->entityList = $parentEntityList;
                        }
                    }

                    continue;
                }

                $foreignEntityType = $defs['entity'] ?? null;

                if ($foreignEntityType) {
                    if ($this->acl->tryCheck($foreignEntityType)) {
                        continue;
                    }

                    if ($this->user->isPortal()) {
                        if ($foreignEntityType === 'Account' || $foreignEntityType === 'Contact') {
                            continue;
                        }
                    }
                }

                if ($hasField) {
                    if (!in_array($link, $forbiddenFieldList)) {
                        continue;
                    }

                    unset($data->entityDefs->$entityType->fields->$link);
                }

                unset($data->entityDefs->$entityType->links->$link);

                if (
                    isset($data->clientDefs) &&
                    isset($data->clientDefs->$entityType) &&
                    isset($data->clientDefs->$entityType->relationshipPanels)
                ) {
                    unset($data->clientDefs->$entityType->relationshipPanels->$link);
                }
            }
        }

        unset($data->entityDefs->Settings);

        $dashletList = array_keys($this->metadata->get(['dashlets'], []));

        foreach ($dashletList as $item) {
            $aclScope = $this->metadata->get(['dashlets', $item, 'aclScope']);

            if ($aclScope && !$this->acl->tryCheck($aclScope)) {
                unset($data->dashlets->$item);
            }
        }

        unset($data->authenticationMethods);
        unset($data->formula);

        foreach (($this->metadata->get(['app', 'metadata', 'aclDependencies']) ?? []) as $target => $item) {
            $targetArr = explode('.', $target);

            if (is_string($item)) {
                $depArr = explode('.', $item);
                $pointer = $data;

                foreach ($depArr as $k) {
                    if (!isset($pointer->$k)) {
                        continue 2;
                    }

                    $pointer = $pointer->$k;
                }
            }
            else if (is_array($item)) {
                $aclScope = $item['scope'] ?? null;
                $aclField = $item['field'] ?? null;

                if (!$aclScope) {
                    continue;
                }

                if (!$this->acl->tryCheck($aclScope)) {
                    continue;
                }

                if ($aclField && in_array($aclField, $this->acl->getScopeForbiddenFieldList($aclScope))) {
                    continue;
                }
            }

            $pointer = $data;

            foreach ($targetArr as $i => $k) {
                if ($i === count($targetArr) - 1) {
                    $pointer->$k = $this->metadata->get($targetArr);

                    break;
                }

                if (!isset($pointer->$k)) {
                    $pointer->$k = (object) [];
                }

                $pointer = $pointer->$k;
            }
        }

        return $data;
    }
}
