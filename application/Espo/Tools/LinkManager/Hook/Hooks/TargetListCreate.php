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

namespace Espo\Tools\LinkManager\Hook\Hooks;

use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Templates\Entities\Company;
use Espo\Core\Templates\Entities\Person;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Type\AttributeType;
use Espo\Tools\LinkManager\Hook\CreateHook;
use Espo\Tools\LinkManager\Params;
use Espo\Tools\LinkManager\Type;
use Espo\Modules\Crm\Entities\TargetList;
use Espo\Core\Utils\Metadata;

class TargetListCreate implements CreateHook
{
    public function __construct(private Metadata $metadata)
    {}

    public function process(Params $params): void
    {
        $toProcess =
            (
                $params->getEntityType() === TargetList::ENTITY_TYPE ||
                $params->getForeignEntityType() === TargetList::ENTITY_TYPE
            ) &&
            $params->getType() === Type::MANY_TO_MANY;

        if (!$toProcess) {
            return;
        }

        [$entityType, $link, $foreignLink] = $params->getEntityType() === TargetList::ENTITY_TYPE ?
            [
                $params->getForeignEntityType(),
                $params->getForeignLink(),
                $params->getLink(),
            ] :
            [
                $params->getEntityType(),
                $params->getLink(),
                $params->getForeignLink(),
            ];

        if (!$entityType) {
            return;
        }

        $type = $this->metadata->get(['scopes', $entityType, 'type']);

        if (!in_array($type, [Person::TEMPLATE_TYPE, Company::TEMPLATE_TYPE])) {
            return;
        }

        if ($link !== 'targetLists') {
            return;
        }

        $this->processInternal($entityType, $link, $foreignLink);
    }

    private function processInternal(string $entityType, string $link, string $foreignLink): void
    {
        $this->metadata->set('entityDefs', TargetList::ENTITY_TYPE, [
            'links' => [
                $foreignLink => [
                    RelationParam::ADDITIONAL_COLUMNS => [
                        'optedOut' => [
                            'type' => AttributeType::BOOL,
                        ]
                    ],
                    'columnAttributeMap' => [
                        'optedOut' => 'isOptedOut',
                    ],
                ],
            ],
        ]);

        $this->metadata->set('entityDefs', $entityType, [
            'links' => [
                $link => [
                    'columnAttributeMap' => [
                        'optedOut' => 'targetListIsOptedOut',
                    ],
                ],
            ],
            'fields' => [
                'targetListIsOptedOut' => [
                    'type' => FieldType::BOOL,
                    FieldParam::NOT_STORABLE => true,
                    'readOnly' => true,
                    'disabled' => true,
                ],
            ]
        ]);

        $this->metadata->set('clientDefs', TargetList::ENTITY_TYPE, [
            'relationshipPanels' => [
                $foreignLink => [
                    'actionList' => [
                        [
                            'label' => 'Unlink All',
                            'action' => 'unlinkAllRelated',
                            'acl' => 'edit',
                            'data' => [
                                'link' => $foreignLink,
                            ],
                        ],
                    ],
                    'rowActionsView' => 'crm:views/target-list/record/row-actions/default',
                    'view' => 'crm:views/target-list/record/panels/relationship',
                    'massSelect' => true,
                    'removeDisabled' => true,
                ],
            ],
        ]);

        $this->metadata->set('recordDefs', TargetList::ENTITY_TYPE, [
            'relationships' => [
                $foreignLink => [
                    'massLink' => true,
                    'linkRequiredForeignAccess' => 'read',
                    'mandatoryAttributeList' => ['targetListIsOptedOut'],
                ],
            ],
        ]);

        $targetLinkList = $this->metadata->get(['scopes', TargetList::ENTITY_TYPE, 'targetLinkList']) ?? [];

        if (!in_array($foreignLink, $targetLinkList)) {
            $targetLinkList[] = $foreignLink;

            $this->metadata->set('scopes', TargetList::ENTITY_TYPE, [
                'targetLinkList' => $targetLinkList,
            ]);
        }

        $this->metadata->save();
    }
}
