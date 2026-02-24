<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Tools\EntityManager\Hook\Hooks;

use Espo\Core\DataManager;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\InjectableFactory;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Templates\Entities\Base;
use Espo\Core\Templates\Entities\BasePlus;
use Espo\Core\Templates\Entities\CategoryTree;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Type\RelationType;
use Espo\Tools\EntityManager\CreateParams;
use Espo\Tools\EntityManager\DeleteParams;
use Espo\Tools\EntityManager\EntityManager;
use Espo\Tools\EntityManager\Hook\UpdateHook;
use Espo\Tools\EntityManager\Params;
use Espo\Tools\LayoutManager\LayoutCustomizer;
use Espo\Tools\LayoutManager\LayoutName;

/**
 * @noinspection PhpUnused
 */
class CategoriesUpdateHook implements UpdateHook
{
    private const string PARAM = 'categories';
    private const string FIELD = 'category';

    public function __construct(
        private InjectableFactory $injectableFactory,
        private Language $defaultLanguage,
        private Metadata $metadata,
        private DataManager $dataManager,
        private LayoutCustomizer $layoutCustomizer,
        private Log $log,
    ) {}

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Error
     * @throws Conflict
     */
    public function process(Params $params, Params $previousParams): void
    {
        if (!in_array($params->getType(), [BasePlus::TEMPLATE_TYPE, Base::TEMPLATE_TYPE])) {
            return;
        }

        if ($params->get(self::PARAM) && !$previousParams->get(self::PARAM)) {
            $this->add($params->getName());
        } else if (!$params->get(self::PARAM) && $previousParams->get(self::PARAM)) {
            $this->remove($params->getName());
        }
    }

    /**
     * @throws BadRequest
     * @throws Conflict
     * @throws Error
     */
    private function add(string $name): void
    {
        $entityType = $this->composeEntityType($name);

        if ($this->metadata->get("scopes.$entityType")) {
            $message = "Could not create category entity type $entityType as the same entity type exists";

            $this->log->warning($message);

            return;
        }

        $createParams = new CreateParams(
            forceCreate: true,
            replaceData: [
                'subjectEntityType' => $name,
            ],
            skipCustomPrefix: true,
            isNotRemovable: true,
            addTab: false,
        );

        $this->getEntityManagerTool()->create(
            name: $entityType,
            type: CategoryTree::TEMPLATE_TYPE,
            params: [
                'labelSingular' => $this->defaultLanguage->translateLabel($name, 'scopeNames') . ' ' .
                    $this->defaultLanguage->translateLabel('Category', 'entityNameParts', 'EntityManager'),
                'labelPlural' => $this->defaultLanguage->translateLabel($name, 'scopeNames') . ' ' .
                    $this->defaultLanguage->translateLabel('Category', 'entityNamePartsPlural', 'EntityManager')
            ],
            createParams: $createParams,
        );

        $this->metadata->set('entityDefs', $name, [
            'fields' => [
                self::FIELD => [
                    FieldParam::TYPE => FieldType::LINK,
                    'audited' => true,
                    'view' => 'views/fields/link-category-tree'
                ]
            ],
            'links' => [
                self::FIELD => [
                    RelationParam::TYPE => RelationType::BELONGS_TO,
                    RelationParam::ENTITY => $entityType,
                ]
            ],
        ]);

        $this->metadata->set('clientDefs', $name, [
            'views' => [
                'list' => 'views/list-with-categories',
            ],
            'modalViews' => [
                'select' => 'views/modals/select-records-with-categories',
            ],
        ]);

        $this->metadata->save();
        $this->dataManager->rebuild();

        $this->layoutCustomizer->addDetailField($name, self::FIELD, LayoutName::DETAIL);
        $this->layoutCustomizer->addDetailField($name, self::FIELD, LayoutName::DETAIL_SMALL);
    }

    /**
     * @throws Forbidden
     * @throws Error
     */
    public function remove(string $name): void
    {
        $entityType = $this->composeEntityType($name);

        $deleteParams = new DeleteParams(
            forceRemove: true,
        );

        $this->getEntityManagerTool()->delete($entityType, $deleteParams);

        $this->metadata->delete('entityDefs', $name, [
            'fields.' . self::FIELD,
            'links.' . self::FIELD,
        ]);

        $this->metadata->delete('clientDefs', $name, [
            'views.list',
            'modalViews.select',
        ]);

        $this->metadata->save();
        $this->dataManager->rebuild();

        $this->layoutCustomizer->removeInDetail($name, self::FIELD, LayoutName::DETAIL);
        $this->layoutCustomizer->removeInDetail($name, self::FIELD, LayoutName::DETAIL_SMALL);
    }

    private function getEntityManagerTool(): EntityManager
    {
        return $this->injectableFactory->create(EntityManager::class);
    }

    private function composeEntityType(string $name): string
    {
        return $name . 'Category';
    }
}
