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

namespace Espo\Tools\EntityManager\Hook\Hooks;

use Espo\Core\DataManager;
use Espo\Core\Exceptions\Error;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Type\RelationType;
use Espo\Tools\EntityManager\Hook\UpdateHook;
use Espo\Tools\EntityManager\Params;

/**
 * @noinspection PhpUnused
 */
class CollaboratorsUpdateHook implements UpdateHook
{
    private const PARAM = 'collaborators';
    private const FIELD = Field::COLLABORATORS;
    private const RELATION_NAME = 'entityCollaborator';

    private const DEFAULT_MAX_COUNT = 30;

    public function __construct(
        private Metadata $metadata,
        private Log $log,
        private DataManager $dataManager,
    ) {}

    /**
     * @throws Error
     */
    public function process(Params $params, Params $previousParams): void
    {
        if ($params->get(self::PARAM) && !$previousParams->get(self::PARAM)) {
            $this->add($params->getName());
        } else if (!$params->get(self::PARAM) && $previousParams->get(self::PARAM)) {
            $this->remove($params->getName());
        }
    }

    /**
     * @throws Error
     */
    private function add(string $entityType): void
    {
        if ($this->metadata->get("entityDefs.$entityType.links." . self::FIELD . ".isCustom")) {
            $this->log->warning("Cannot enable collaborators for $entityType as the link already exists.");

            return;
        }

        $this->metadata->set('entityDefs', $entityType, [
            'fields' => [
                self::FIELD => [
                    'type' => FieldType::LINK_MULTIPLE,
                    'view' => 'views/fields/collaborators',
                    'maxCount' => self::DEFAULT_MAX_COUNT,
                    'fieldManagerParamList' => [
                        'readOnly',
                        'readOnlyAfterCreate',
                        'audited',
                        'autocompleteOnEmpty',
                        'maxCount',
                        'inlineEditDisabled',
                        'tooltipText'
                    ]
                ],
            ],
            'links' => [
                self::FIELD => [
                    'type' => RelationType::HAS_MANY,
                    'entity' => User::ENTITY_TYPE,
                    RelationParam::RELATION_NAME => self::RELATION_NAME,
                    'layoutRelationshipsDisabled' => true,
                ],
            ],
        ]);

        $this->metadata->set('entityAcl', $entityType, [
            'links' => [
                self::FIELD => [
                    'readOnly' => true,
                ],
            ],
        ]);

        $this->metadata->save();

        $this->dataManager->rebuild([$entityType]);
    }

    private function remove(string $entityType): void
    {
        $field = self::FIELD;

        if (
            $this->metadata->get("entityDefs.$entityType.links.$field.isCustom") &&
            $this->metadata->get("entityDefs.$entityType.links.$field.relationName") !== self::RELATION_NAME
        ) {
            return;
        }

        $this->metadata->delete('entityDefs', $entityType, [
            'fields.' . self::FIELD,
            'links.' . self::FIELD,
        ]);

        $this->metadata->delete('entityAcl', $entityType, [
            'links.' . self::FIELD,
        ]);

        $this->metadata->save();
    }
}
