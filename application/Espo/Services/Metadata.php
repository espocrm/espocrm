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

class Metadata extends \Espo\Core\Services\Base
{
    protected function init()
    {
        $this->addDependency('metadata');
        $this->addDependency('acl');
    }

    protected function getMetadata()
    {
        return $this->getInjection('metadata');
    }

    protected function getAcl()
    {
        return $this->getInjection('acl');
    }

    protected function getDefaultLanguage()
    {
        return $this->getInjection('container')->get('defaultLanguage');
    }

    protected function getLanguage()
    {
        return $this->getInjection('container')->get('language');
    }

    public function getDataForFrontend()
    {
        $data = $this->getMetadata()->getAllForFrontend();

        if (!$this->getUser()->isAdmin()) {
            $scopeList = array_keys($this->getMetadata()->get(['scopes'], []));
            foreach ($scopeList as $scope) {
                if (!$this->getMetadata()->get(['scopes', $scope, 'entity'])) continue;
                if (in_array($scope, ['Reminder'])) continue;
                if (!$this->getAcl()->check($scope)) {
                    unset($data->entityDefs->$scope);
                    unset($data->clientDefs->$scope);
                    unset($data->entityAcl->$scope);
                    unset($data->scopes->$scope);
                }
            }

            $entityTypeList = array_keys(get_object_vars($data->entityDefs));
            foreach ($entityTypeList as $entityType) {
                $linksDefs = $this->getMetadata()->get(['entityDefs', $entityType, 'links'], []);

                $fobiddenFieldList = $this->getAcl()->getScopeForbiddenFieldList($entityType);

                foreach ($linksDefs as $link => $defs) {
                    $type = $defs['type'] ?? null;

                    $hasField = !!$this->getMetadata()->get(['entityDefs', $entityType, 'fields', $link]);

                    if ($type === 'belongsToParent') {
                        if ($hasField) {
                            $parentEntityList = $this->getMetadata()->get(['entityDefs', $entityType, 'fields', $link, 'entityList']);
                            if (is_array($parentEntityList)) {
                                foreach ($parentEntityList as $i => $e) {
                                    if (!$this->getAcl()->check($e)) {
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
                    if ($this->getAcl()->check($foreignEntityType)) continue;

                    if ($this->getUser()->isPortal()) {
                        if ($foreignEntityType === 'Account' || $foreignEntityType === 'Contact') {
                            continue;
                        }
                    }

                    if ($hasField) {
                        if (!in_array($link, $fobiddenFieldList)) {
                            continue;
                        }
                        unset($data->entityDefs->$entityType->fields->$link);
                    }

                    unset($data->entityDefs->$entityType->links->$link);

                    if (
                        isset($data->clientDefs)
                        &&
                        isset($data->clientDefs->$entityType)
                        &&
                        isset($data->clientDefs->$entityType->relationshipPanels)
                    ) {
                        unset($data->clientDefs->$entityType->relationshipPanels->$link);
                    }
                }
            }

            unset($data->entityDefs->Settings);

            $dashletList = array_keys($this->getMetadata()->get(['dashlets'], []));

            foreach ($dashletList as $item) {
                $aclScope = $this->getMetadata()->get(['dashlets', $item, 'aclScope']);
                if ($aclScope && !$this->getAcl()->check($aclScope)) {
                    unset($data->dashlets->$item);
                }
            }

            unset($data->authenticationMethods);
            unset($data->formula);

            foreach (($this->getMetadata()->get(['app', 'metadata', 'aclDependencies']) ?? []) as $target => $item) {
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
                } else if (is_array($item)) {
                    $aclScope = $item['scope'] ?? null;;
                    $aclField = $item['field'] ?? null;
                    if (!$aclScope) continue;
                    if (!$this->getAcl()->check($aclScope)) continue;
                    if ($aclField && in_array($aclField, $this->getAcl()->getScopeForbiddenFieldList($aclScope))) continue;
                }

                $pointer = $data;
                foreach ($targetArr as $i => $k) {
                    if ($i === count($targetArr) - 1) {
                        $pointer->$k = $this->getMetadata()->get($targetArr);
                        break;
                    }
                    if (!isset($pointer->$k)) {
                        $pointer->$k = (object) [];
                    }
                    $pointer = $pointer->$k;
                }
            }
        }

        return $data;
    }
}
