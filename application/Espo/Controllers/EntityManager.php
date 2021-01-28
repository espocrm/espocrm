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
    Exceptions\Error,
    Exceptions\Forbidden,
    Exceptions\BadRequest,
    Api\Request,
    DataManager,
    Utils\Config,
    Utils\Config\ConfigWriter,
};

class EntityManager
{
    protected $user;
    protected $dataManager;
    protected $config;
    protected $entityManagerTool;
    protected $configWriter;

    public function __construct(
        User $user,
        DataManager $dataManager,
        Config $config,
        EntityManagerTool $entityManagerTool,
        ConfigWriter $configWriter
    ) {
        $this->user = $user;
        $this->dataManager = $dataManager;
        $this->config = $config;
        $this->entityManagerTool = $entityManagerTool;
        $this->configWriter = $configWriter;

        $this->checkControllerAccess();
    }

    protected function checkControllerAccess()
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }
    }

    public function postActionCreateEntity(Request $request)
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

        $params['kanbanViewMode'] = !empty($data['kanbanViewMode']);
        if (!empty($data['kanbanStatusIgnoreList'])) {
            $params['kanbanStatusIgnoreList'] = $data['kanbanStatusIgnoreList'];
        }

        $result = $this->entityManagerTool->create($name, $type, $params);

        if ($result) {
            $tabList = $this->config->get('tabList', []);

            if (!in_array($name, $tabList)) {
                $tabList[] = $name;

                $this->configWriter->set('tabList', $tabList);

                $this->configWriter->save();
            }

            $this->dataManager->rebuild();
        } else {
            throw new Error();
        }

        return true;
    }

    public function postActionUpdateEntity(Request $request)
    {
        $data = $request->getParsedBody();

        $data = get_object_vars($data);

        if (empty($data['name'])) {
            throw new BadRequest();
        }

        $name = $data['name'];
        $name = filter_var($name, \FILTER_SANITIZE_STRING);

        $result = $this->entityManagerTool->update($name, $data);

        if ($result) {
            $this->dataManager->clearCache();
        } else {
            throw new Error();
        }

        return true;
    }

    public function postActionRemoveEntity(Request $request)
    {
        $data = $request->getParsedBody();

        $data = get_object_vars($data);

        if (empty($data['name'])) {
            throw new BadRequest();
        }

        $name = $data['name'];
        $name = filter_var($name, \FILTER_SANITIZE_STRING);

        $result = $this->entityManagerTool->delete($name);

        if ($result) {
            $tabList = $this->config->get('tabList', []);

            if (($key = array_search($name, $tabList)) !== false) {
                unset($tabList[$key]);
                $tabList = array_values($tabList);
            }

            $this->configWriter->set('tabList', $tabList);

            $this->configWriter->save();

            $this->dataManager->clearCache();
        }
        else {
            throw new Error();
        }

        return true;
    }

    public function postActionCreateLink(Request $request)
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

        $result = $this->entityManagerTool->createLink($params);

        if ($result) {
            $this->dataManager->rebuild();
        } else {
            throw new Error();
        }

        return true;
    }

    public function postActionUpdateLink(Request $request)
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

        $additionalParamList = [];

        $params = [];
        foreach ($paramList as $item) {
            if (array_key_exists($item, $data)) {
                $params[$item] = filter_var($data[$item], \FILTER_SANITIZE_STRING);
            }
        }

        foreach ($additionalParamList as $item) {
            $params[$item] = filter_var($data[$item], \FILTER_SANITIZE_STRING);
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

        $result = $this->entityManagerTool->updateLink($params);

        if ($result) {
            $this->dataManager->clearCache();
        } else {
            throw new Error();
        }

        return true;
    }

    public function postActionRemoveLink(Request $request)
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

        $result = $this->entityManagerTool->deleteLink($d);

        if ($result) {
            $this->dataManager->clearCache();
        } else {
            throw new Error();
        }

        return true;
    }

    public function postActionFormula(Request $request)
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

        $this->dataManager->clearCache();

        return true;
    }

    public function postActionResetToDefault(Request $request)
    {
        $data = $request->getParsedBody();

        if (empty($data->scope)) {
            throw new BadRequest();
        }

        $this->entityManagerTool->resetToDefaults($data->scope);
        
        $this->dataManager->clearCache();

        return true;
    }
}
