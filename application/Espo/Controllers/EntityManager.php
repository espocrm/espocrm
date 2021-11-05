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

namespace Espo\Controllers;

use Espo\{
    Entities\User,
    Tools\EntityManager\EntityManager as EntityManagerTool,
};

use Espo\Core\{
    Exceptions\Forbidden,
    Exceptions\BadRequest,
    Api\Request,
};

class EntityManager
{
    private $user;

    private $entityManagerTool;

    public function __construct(User $user, EntityManagerTool $entityManagerTool)
    {
        $this->user = $user;
        $this->entityManagerTool = $entityManagerTool;

        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }
    }

    public function postActionCreateEntity(Request $request): bool
    {
        $data = $request->getParsedBody();

        $data = get_object_vars($data);

        if (empty($data['name']) || empty($data['type'])) {
            throw new BadRequest();
        }

        $name = $data['name'];
        $type = $data['type'];

        $name = filter_var($name, \FILTER_SANITIZE_STRING);
        $type = filter_var($type, \FILTER_SANITIZE_STRING);

        $params = [];

        if (!empty($data['labelSingular'])) {
            $params['labelSingular'] = $data['labelSingular'];
        }
        if (!empty($data['labelPlural'])) {
            $params['labelPlural'] = $data['labelPlural'];
        }
        if (!empty($data['stream'])) {
            $params['stream'] = $data['stream'];
        }
        if (!empty($data['disabled'])) {
            $params['disabled'] = $data['disabled'];
        }
        if (!empty($data['sortBy'])) {
            $params['sortBy'] = $data['sortBy'];
        }
        if (!empty($data['sortDirection'])) {
            $params['asc'] = $data['sortDirection'] === 'asc';
        }
        if (isset($data['textFilterFields']) && is_array($data['textFilterFields'])) {
            $params['textFilterFields'] = $data['textFilterFields'];
        }
        if (!empty($data['color'])) {
            $params['color'] = $data['color'];
        }
        if (!empty($data['iconClass'])) {
            $params['iconClass'] = $data['iconClass'];
        }
        if (isset($data['fullTextSearch'])) {
            $params['fullTextSearch'] = $data['fullTextSearch'];
        }
        if (isset($data['countDisabled'])) {
            $params['countDisabled'] = $data['countDisabled'];
        }
        if (isset($data['optimisticConcurrencyControl'])) {
            $params['optimisticConcurrencyControl'] = $data['optimisticConcurrencyControl'];
        }

        $params['kanbanViewMode'] = !empty($data['kanbanViewMode']);
        if (!empty($data['kanbanStatusIgnoreList'])) {
            $params['kanbanStatusIgnoreList'] = $data['kanbanStatusIgnoreList'];
        }

        $this->entityManagerTool->create($name, $type, $params);

        return true;
    }

    public function postActionUpdateEntity(Request $request): bool
    {
        $data = $request->getParsedBody();

        $data = get_object_vars($data);

        if (empty($data['name'])) {
            throw new BadRequest();
        }

        $name = $data['name'];

        $name = filter_var($name, \FILTER_SANITIZE_STRING);

        $this->entityManagerTool->update($name, $data);

        return true;
    }

    public function postActionRemoveEntity(Request $request): bool
    {
        $data = $request->getParsedBody();

        $data = get_object_vars($data);

        if (empty($data['name'])) {
            throw new BadRequest();
        }

        $name = $data['name'];

        $name = filter_var($name, \FILTER_SANITIZE_STRING);

        $this->entityManagerTool->delete($name);

        return true;
    }

    public function postActionCreateLink(Request $request): bool
    {
        $data = $request->getParsedBody();

        $data = get_object_vars($data);

        $paramList = [
            'entity',
            'link',
            'linkForeign',
            'label',
            'linkType',
        ];

        $additionalParamList = [
            'entityForeign',
            'relationName',
            'labelForeign',
        ];

        $params = [];

        foreach ($paramList as $item) {
            if (empty($data[$item])) {
                throw new BadRequest();
            }

            $params[$item] = filter_var($data[$item], \FILTER_SANITIZE_STRING);
        }

        foreach ($additionalParamList as $item) {
            $params[$item] = filter_var($data[$item] ?? null, \FILTER_SANITIZE_STRING);
        }

        $params['labelForeign'] = $params['labelForeign'] ?? $params['linkForeign'];

        if (array_key_exists('linkMultipleField', $data)) {
            $params['linkMultipleField'] = $data['linkMultipleField'];
        }
        if (array_key_exists('linkMultipleFieldForeign', $data)) {
            $params['linkMultipleFieldForeign'] = $data['linkMultipleFieldForeign'];
        }

        if (array_key_exists('audited', $data)) {
            $params['audited'] = $data['audited'];
        }
        if (array_key_exists('auditedForeign', $data)) {
            $params['auditedForeign'] = $data['auditedForeign'];
        }
        if (array_key_exists('parentEntityTypeList', $data)) {
            $params['parentEntityTypeList'] = $data['parentEntityTypeList'];
        }
        if (array_key_exists('foreignLinkEntityTypeList', $data)) {
            $params['foreignLinkEntityTypeList'] = $data['foreignLinkEntityTypeList'];
        }

        $this->entityManagerTool->createLink($params);

        return true;
    }

    public function postActionUpdateLink(Request $request): bool
    {
        $data = $request->getParsedBody();

        $data = get_object_vars($data);

        $paramList = [
            'entity',
            'entityForeign',
            'link',
            'linkForeign',
            'label',
            'labelForeign',
        ];

        $params = [];

        foreach ($paramList as $item) {
            if (array_key_exists($item, $data)) {
                $params[$item] = filter_var($data[$item], \FILTER_SANITIZE_STRING);
            }
        }

        if (array_key_exists('linkMultipleField', $data)) {
            $params['linkMultipleField'] = $data['linkMultipleField'];
        }
        if (array_key_exists('linkMultipleFieldForeign', $data)) {
            $params['linkMultipleFieldForeign'] = $data['linkMultipleFieldForeign'];
        }

        if (array_key_exists('audited', $data)) {
            $params['audited'] = $data['audited'];
        }
        if (array_key_exists('auditedForeign', $data)) {
            $params['auditedForeign'] = $data['auditedForeign'];
        }
        if (array_key_exists('parentEntityTypeList', $data)) {
            $params['parentEntityTypeList'] = $data['parentEntityTypeList'];
        }
        if (array_key_exists('foreignLinkEntityTypeList', $data)) {
            $params['foreignLinkEntityTypeList'] = $data['foreignLinkEntityTypeList'];
        }

        $this->entityManagerTool->updateLink($params);

        return true;
    }

    public function postActionRemoveLink(Request $request): bool
    {
        $data = $request->getParsedBody();

        $data = get_object_vars($data);

        $paramList = [
            'entity',
            'link',
        ];

        $d = [];

        foreach ($paramList as $item) {
            $d[$item] = filter_var($data[$item], \FILTER_SANITIZE_STRING);
        }

        $this->entityManagerTool->deleteLink($d);

        return true;
    }

    public function postActionFormula(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (empty($data->scope)) {
            throw new BadRequest();
        }

        if (!property_exists($data, 'data')) {
            throw new BadRequest();
        }

        $formulaData = get_object_vars($data->data);

        $this->entityManagerTool->setFormulaData($data->scope, $formulaData);

        return true;
    }

    public function postActionResetToDefault(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (empty($data->scope)) {
            throw new BadRequest();
        }

        $this->entityManagerTool->resetToDefaults($data->scope);

        return true;
    }
}
