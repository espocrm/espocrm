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
use Espo\Core\Exceptions\Error;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Crm\Entities\Account;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\Tools\EntityManager\Hook\UpdateHook;
use Espo\Tools\EntityManager\Params;

/**
 * @noinspection PhpUnused
 */
class LockableUpdateHook implements UpdateHook
{
    private const string PARAM = 'lockable';
    private const string FIELD = Field::IS_LOCKED;

    /** @var string[] */
    private array $enabledByDefaultEntityTypeList = [
        Account::ENTITY_TYPE,
    ];

    public function __construct(
        private Metadata $metadata,
        private Log $log,
        private DataManager $dataManager,
    ) {}

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
        if ($this->metadata->get("entityDefs.$entityType.fields." . self::FIELD . ".isCustom")) {
            $this->log->warning("Cannot enable lockable for $entityType as the field already exists.");

            return;
        }

        if ($this->isEnabledByDefault($entityType)) {
            $this->addEnabledByDefault($entityType);
        } else {
            $this->addInternal($entityType);
        }


        $this->metadata->save();
        $this->dataManager->rebuild([$entityType]);
    }

    private function addEnabledByDefault(string $entityType): void
    {
        $this->metadata->delete('entityDefs', $entityType, [
            'fields.' . self::FIELD,
        ]);
    }

    private function addInternal(string $entityType): void
    {
        $this->metadata->set('entityDefs', $entityType, [
            'fields' => [
                self::FIELD => [
                    FieldParam::TYPE => FieldType::BOOL,
                    FieldParam::READ_ONLY => true,
                    'audited' => true,
                    'fieldManagerParamList' => [
                        'audited',
                        'tooltipText',
                    ],
                    'layoutAvailabilityList' => [
                        'filters',
                        'list',
                    ],
                ],
            ]
        ]);

    }

    private function remove(string $entityType): void
    {
        $this->metadata->delete('entityDefs', $entityType, [
            'fields.' . self::FIELD,
        ]);

        $this->metadata->save();

        // Must be after metadata is saved.
        if ($this->isEnabledByDefault($entityType)) {
            $this->metadata->set('entityDefs', $entityType, [
                'fields' => [
                    self::FIELD => [
                        FieldParam::DISABLED => true,
                    ],
                ],
            ]);

            $this->metadata->save();
        }
    }

    private function isEnabledByDefault(string $entityType): bool
    {
        return in_array($entityType, $this->enabledByDefaultEntityTypeList);
    }
}
