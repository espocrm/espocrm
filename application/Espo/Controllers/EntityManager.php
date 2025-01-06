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

namespace Espo\Controllers;

use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Error;
use Espo\Core\InjectableFactory;
use Espo\Entities\User;
use Espo\Tools\EntityManager\EntityManager as EntityManagerTool;
use Espo\Core\Api\Request;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Tools\ExportCustom\ExportCustom;
use Espo\Tools\ExportCustom\Params as ExportCustomParams;
use Espo\Tools\ExportCustom\Service as ExportCustomService;
use Espo\Tools\LinkManager\LinkManager;
use stdClass;

class EntityManager
{
    /**
     * @throws Forbidden
     */
    public function __construct(
        private User $user,
        private EntityManagerTool $entityManagerTool,
        private LinkManager $linkManager,
        private InjectableFactory $injectableFactory
    ) {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }
    }

    /**
     * @throws BadRequest
     * @throws Error
     * @throws Conflict
     */
    public function postActionCreateEntity(Request $request): stdClass
    {
        $data = $request->getParsedBody();

        $data = get_object_vars($data);

        if (empty($data['name']) || empty($data['type'])) {
            throw new BadRequest();
        }

        $name = $data['name'];
        $type = $data['type'];

        if (!is_string($name) || !is_string($type)) {
            throw new BadRequest();
        }

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

        $name = $this->entityManagerTool->create($name, $type, $params);

        return (object) ['name' => $name];
    }

    /**
     * @throws BadRequest
     * @throws Error
     */
    public function postActionUpdateEntity(Request $request): bool
    {
        $data = $request->getParsedBody();

        $data = get_object_vars($data);

        if (empty($data['name'])) {
            throw new BadRequest();
        }

        $name = $data['name'];

        if (!is_string($name)) {
            throw new BadRequest();
        }

        $this->entityManagerTool->update($name, $data);

        return true;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Error
     */
    public function postActionRemoveEntity(Request $request): bool
    {
        $data = $request->getParsedBody();

        $data = get_object_vars($data);

        if (empty($data['name'])) {
            throw new BadRequest();
        }

        $name = $data['name'];

        if (!is_string($name)) {
            throw new BadRequest();
        }

        $this->entityManagerTool->delete($name);

        return true;
    }

    /**
     * @throws BadRequest
     * @throws Error
     * @throws Conflict
     */
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

            $params[$item] = htmlspecialchars($data[$item]);
        }

        foreach ($additionalParamList as $item) {
            $params[$item] = $data[$item];

            if (is_string($params[$item])) {
                $params[$item] = htmlspecialchars($params[$item]);
            }
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

        if (array_key_exists('layout', $data)) {
            $params['layout'] = $data['layout'];
        }

        if (array_key_exists('layoutForeign', $data)) {
            $params['layoutForeign'] = $data['layoutForeign'];
        }

        if (array_key_exists('selectFilter', $data)) {
            $params['selectFilter'] = $data['selectFilter'];
        }

        if (array_key_exists('selectFilterForeign', $data)) {
            $params['selectFilterForeign'] = $data['selectFilterForeign'];
        }

        /** @var array{
         *     linkType: string,
         *     entity: string,
         *     link: string,
         *     entityForeign: string,
         *     linkForeign: string,
         *     label: string,
         *     labelForeign: string,
         *     relationName?: ?string,
         *     linkMultipleField?: bool,
         *     linkMultipleFieldForeign?: bool,
         *     audited?: bool,
         *     auditedForeign?: bool,
         *     layout?: string,
         *     layoutForeign?: string,
         *     selectFilter?: string,
         *     selectFilterForeign?: string,
         * } $params
         */

        $this->linkManager->create($params);

        return true;
    }

    /**
     * @throws BadRequest
     * @throws Error
     */
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
                $params[$item] = htmlspecialchars($data[$item]);
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

        if (array_key_exists('layout', $data)) {
            $params['layout'] = $data['layout'];
        }

        if (array_key_exists('layoutForeign', $data)) {
            $params['layoutForeign'] = $data['layoutForeign'];
        }

        if (array_key_exists('selectFilter', $data)) {
            $params['selectFilter'] = $data['selectFilter'];
        }

        if (array_key_exists('selectFilterForeign', $data)) {
            $params['selectFilterForeign'] = $data['selectFilterForeign'];
        }

        /**
         * @var array{
         *     entity: string,
         *     link: string,
         *     entityForeign?: ?string,
         *     linkForeign?: ?string,
         *     label?: string,
         *     labelForeign?: string,
         *     linkMultipleField?: bool,
         *     linkMultipleFieldForeign?: bool,
         *     audited?: bool,
         *     auditedForeign?: bool,
         *     parentEntityTypeList?: string[],
         *     foreignLinkEntityTypeList?: string[],
         *     layout?: string,
         *     layoutForeign?: string,
         *     selectFilter?: string,
         *     selectFilterForeign?: string,
         * } $params
         */

        $this->linkManager->update($params);

        return true;
    }

    /**
     * @throws BadRequest
     * @throws Error
     */
    public function postActionRemoveLink(Request $request): bool
    {
        $data = $request->getParsedBody();

        $data = get_object_vars($data);

        $paramList = [
            'entity',
            'link',
        ];

        $params = [];

        foreach ($paramList as $item) {
            $params[$item] = htmlspecialchars($data[$item]);
        }

        /**
         * @var array{
         *   entity?: string,
         *   link?: string,
         * } $params
         */

        $this->linkManager->delete($params);

        return true;
    }


    /**
     * @throws BadRequest
     * @throws Error
     */
    public function postActionUpdateLinkParams(Request $request): bool
    {
        $entityType = $request->getParsedBody()->entityType ?? throw new BadRequest("No entityType.");
        $link = $request->getParsedBody()->link ?? throw new BadRequest("No link.");
        $rawParams = $request->getParsedBody()->params ?? throw new BadRequest("No params.");

        if (!is_string($entityType) || !is_string($link) || !$rawParams instanceof stdClass) {
            throw new BadRequest();
        }

        /** @var array{readOnly?: bool} $params */
        $params = [];

        if (property_exists($rawParams, 'readOnly')) {
            $params['readOnly'] = (bool) $rawParams->readOnly;
        }

        $this->linkManager->updateParams($entityType, $link, $params);

        return true;
    }

    /**
     * @throws BadRequest
     * @throws Error
     */
    public function postActionResetLinkParamsToDefault(Request $request): bool
    {
        $entityType = $request->getParsedBody()->entityType ?? throw new BadRequest("No entityType.");
        $link = $request->getParsedBody()->link ?? throw new BadRequest("No link.");

        if (!is_string($entityType) || !is_string($link)) {
            throw new BadRequest();
        }

        $this->linkManager->resetToDefault($entityType, $link);

        return true;
    }

    /**
     * @throws BadRequest
     * @throws Error
     */
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

    /**
     * @throws BadRequest
     */
    public function postActionResetFormulaToDefault(Request $request): bool
    {
        $data = $request->getParsedBody();

        $scope = $data->scope ?? null;
        $type = $data->type ?? null;

        if (!$scope || !$type) {
            throw new BadRequest();
        }

        $this->entityManagerTool->resetFormulaToDefault($scope, $type);

        return true;
    }

    /**
     * @throws BadRequest
     * @throws Error
     */
    public function postActionResetToDefault(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (empty($data->scope)) {
            throw new BadRequest();
        }

        $this->entityManagerTool->resetToDefaults($data->scope);

        return true;
    }

    /**
     * @throws BadRequest
     */
    public function postActionExportCustom(Request $request): stdClass
    {
        $data = $request->getParsedBody();

        $name = $data->name ?? null;
        $version = $data->version ?? null;
        $author = $data->author ?? null;
        $module = $data->module ?? null;
        $description = $data->description ?? null;

        if (
            !is_string($name) ||
            !is_string($version) ||
            !is_string($author) ||
            !is_string($module) ||
            !is_string($description) && !is_null($description)
        ) {
            throw new BadRequest();
        }

        $params = new ExportCustomParams(
            name: $name,
            module: $module,
            version: $version,
            author: $author,
            description: $description
        );

        $export = $this->injectableFactory->create(ExportCustom::class);
        $service = $this->injectableFactory->create(ExportCustomService::class);

        $service->storeToConfig($params);

        $result = $export->process($params);

        return (object) ['id' => $result->getAttachmentId()];
    }
}
