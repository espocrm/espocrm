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

namespace Espo\Tools\App;

use Espo\Core\Acl;
use Espo\Core\Utils\Metadata as MetadataUtil;
use Espo\Core\Utils\ObjectUtil;
use Espo\Core\Utils\Util;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Reminder;
use Espo\Tools\App\Metadata\AclDependencyProvider;
use stdClass;

class MetadataService
{
    private const ANY_KEY = '__ANY__';

    public function __construct(
        private Acl $acl,
        private MetadataUtil $metadata,
        private User $user,
        private AclDependencyProvider $aclDependencyProvider
    ) {}

    public function getDataForFrontend(): stdClass
    {
        $data = $this->metadata->getAll();

        $hiddenPathList = $this->metadata->get(['app', 'metadata', 'frontendHiddenPathList'], []);

        foreach ($hiddenPathList as $row) {
            $this->removeDataByPath($row, $data);
        }

        if ($this->user->isAdmin()) {
            return $data;
        }

        $data = ObjectUtil::clone($data);

        $hiddenPathList = $this->metadata->get(['app', 'metadata', 'frontendNonAdminHiddenPathList'], []);

        foreach ($hiddenPathList as $row) {
            $this->removeDataByPath($row, $data);
        }

        /** @var string[] $scopeList */
        $scopeList = array_keys($this->metadata->get(['entityDefs'], []));

        foreach ($scopeList as $scope) {
            $isEntity = $this->metadata->get(['scopes', $scope, 'entity']);

            if ($isEntity === false) {
                continue;
            }

            if ($scope === Reminder::ENTITY_TYPE) {
                continue;
            }

            $isAllowed = $isEntity !== null && $this->acl->tryCheck($scope);

            if (!$isAllowed) {
                unset($data->entityDefs->$scope);
                unset($data->clientDefs->$scope);
                unset($data->entityAcl->$scope);
                unset($data->scopes->$scope);
                unset($data->logicDefs->$scope);
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

                if (isset($data->clientDefs->$entityType->relationshipPanels)) {
                    unset($data->clientDefs->$entityType->relationshipPanels->$link);
                }
            }
        }

        unset($data->entityDefs->Settings);

        /** @var string[] $dashletList */
        $dashletList = array_keys($this->metadata->get(['dashlets'], []));

        foreach ($dashletList as $item) {
            $aclScope = $this->metadata->get(['dashlets', $item, 'aclScope']);

            if ($aclScope && !$this->acl->tryCheck($aclScope)) {
                unset($data->dashlets->$item);
            }
        }

        unset($data->authenticationMethods);
        unset($data->formula);

        foreach ($this->aclDependencyProvider->get() as $dependencyItem) {
            $aclScope = $dependencyItem->getScope();
            $aclField = $dependencyItem->getField();

            if (!$aclScope) {
                continue;
            }

            if (!$this->acl->tryCheck($aclScope)) {
                continue;
            }

            if (
                $aclField &&
                in_array($aclField, $this->acl->getScopeForbiddenFieldList($aclScope))
            ) {
                continue;
            }

            $targetArr = explode('.', $dependencyItem->getTarget());

            $pointer = $data;

            $value = $this->metadata->getObjects($targetArr);

            if ($value === null) {
                // Important.
                continue;
            }

            foreach ($targetArr as $i => $k) {
                if ($i === count($targetArr) - 1) {
                    $pointer->$k = $value;

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

    /**
     *
     * @param string[] $row
     * @param stdClass $data
     */
    private function removeDataByPath($row, &$data): void
    {
        $p = &$data;
        $path = [&$p];

        foreach ($row as $i => $item) {
            if (is_array($item)) {
                break;
            }

            if ($item === self::ANY_KEY) {
                foreach (get_object_vars($p) as &$v) {
                    $this->removeDataByPath(
                        array_slice($row, $i + 1),
                        $v
                    );
                }

                return;
            }

            if (!property_exists($p, $item)) {
                break;
            }

            if ($i == count($row) - 1) {
                unset($p->$item);

                $o = &$p;

                for ($j = $i - 1; $j > 0; $j--) {
                    if (is_object($o) && !count(get_object_vars($o))) {
                        $o = &$path[$j];
                        $k = $row[$j];

                        unset($o->$k);
                    } else {
                        break;
                    }
                }
            } else {
                $p = &$p->$item;
                $path[] = &$p;
            }
        }
    }

    public function getDataForFrontendByKey(?string $key): mixed
    {
        $data = $this->getDataForFrontend();

        return Util::getValueByKey($data, $key);
    }
}
