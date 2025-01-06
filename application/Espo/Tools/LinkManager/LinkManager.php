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

namespace Espo\Tools\LinkManager;

use Espo\Core\DataManager;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Error;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Route;
use Espo\Core\Utils\Util;
use Espo\ORM\Defs\Params\EntityParam;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Entity;
use Espo\ORM\Type\RelationType;
use Espo\Tools\LinkManager\Hook\HookProcessor as LinkHookProcessor;
use Espo\Tools\LinkManager\Params as LinkParams;
use Espo\Tools\LinkManager\Type as LinkType;
use Espo\Tools\EntityManager\NameUtil;

/**
 * Administration > Entity Manager > {Entity Type} > Relationships.
 */
class LinkManager
{
    private const MANY_TO_MANY = 'manyToMany';
    private const MANY_TO_ONE = 'manyToOne';
    private const ONE_TO_MANY = 'oneToMany';
    private const CHILDREN_TO_PARENT = 'childrenToParent';
    private const ONE_TO_ONE_LEFT = 'oneToOneLeft';
    private const ONE_TO_ONE_RIGHT = 'oneToOneRight';

    // 64 - 3
    private const MAX_LINK_NAME_LENGTH = 61;

    public function __construct(
        private Metadata $metadata,
        private Language $language,
        private Language $baseLanguage,
        private DataManager $dataManager,
        private LinkHookProcessor $linkHookProcessor,
        private NameUtil $nameUtil,
        private Route $routeUtil
    ) {}

    /**
     * @param array{
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
     *     parentEntityTypeList?: string[],
     *     foreignLinkEntityTypeList?: string[],
     * } $params
     * @throws BadRequest
     * @throws Error
     * @throws Conflict
     */
    public function create(array $params): void
    {
        $linkType = $params['linkType'];

        $entity = $params['entity'];
        $link = trim($params['link']);

        $entityForeign = $params['entityForeign'];
        $linkForeign = trim($params['linkForeign']);

        $label = $params['label'];
        $labelForeign = $params['labelForeign'];

        $relationName = null;
        $dataRight = null;

        if (empty($linkType)) {
            throw new BadRequest("No link type.");
        }

        if (empty($entity)) {
            throw new BadRequest("No entity.");
        }

        if (empty($link) || empty($linkForeign)) {
            throw new BadRequest("No link or link-foreign.");
        }

        if ($linkType === self::MANY_TO_MANY) {
            $relationName = !empty($params['relationName']) ?
                $params['relationName'] :
                lcfirst($entity) . $entityForeign;

            if ($relationName[0] !== 'c' || !preg_match('/[A-Z]/', $relationName[1])) {
                $relationName = $this->nameUtil->addCustomPrefix($relationName);
            }

            if ($this->isNameTooLong($relationName)) {
                throw new Error("Relation name is too long.");
            }

            if (preg_match('/[^a-z]/', $relationName[0])) {
                throw new Error("Relation name should start with a lower case letter.");
            }

            if ($this->metadata->get(['scopes', ucfirst($relationName)])) {
                throw new Conflict("Entity with the same name '$relationName' exists.");
            }

            if ($this->nameUtil->relationshipExists($relationName)) {
                throw new Conflict("Relationship with the same name '$relationName' exists.");
            }
        }

        if (!$this->isScopeCustom($entity)) {
            $link = $this->nameUtil->addCustomPrefix($link);
        }

        if (!$entityForeign || !$this->isScopeCustom($entityForeign)) {
            $linkForeign = $this->nameUtil->addCustomPrefix($linkForeign);
        }

        $linkParams = LinkParams::createBuilder()
            ->setType($linkType)
            ->setEntityType($entity)
            ->setForeignEntityType($entityForeign)
            ->setLink($link)
            ->setForeignLink($linkForeign)
            ->setName($relationName)
            ->build();

        if (is_numeric($link[0]) || is_numeric($linkForeign[0])) {
            throw new Error('Bad link name.');
        }

        if (
            $this->isNameTooLong($link) ||
            $this->isNameTooLong($linkForeign)
        ) {
            throw new Error("Link name is too long.");
        }

        if (preg_match('/[^a-z]/', $link[0])) {
            throw new Error("Link name should start with a lower case letter.");
        }

        if (preg_match('/[^a-z]/', $linkForeign[0])) {
            throw new Error("Link name should start with a lower case letter.");
        }

        if (in_array($link, NameUtil::LINK_FORBIDDEN_NAME_LIST)) {
            throw new Conflict("Link name '$link' is not allowed.");
        }

        if (in_array($linkForeign, NameUtil::LINK_FORBIDDEN_NAME_LIST)) {
            throw new Conflict("Link name '$linkForeign' is not allowed.");
        }

        if (!$this->isScopeCustomizable($entity)) {
            throw new Error("Entity type '$entity' is not customizable.");
        }

        if ($entityForeign && !$this->isScopeCustomizable($entityForeign)) {
            throw new Error("Entity type '$entityForeign' is not customizable.");
        }

        foreach ($this->routeUtil->getFullList() as $route) {
            if ($route->getRoute() === "/$entity/:id/$link") {
                throw new Conflict("Link name '$link' conflicts with existing API endpoint.");
            }
        }

        if ($entityForeign) {
            foreach ($this->routeUtil->getFullList() as $route) {
                if ($route->getRoute() === "/$entityForeign/:id/$linkForeign") {
                    throw new Conflict("Link name '$linkForeign' conflicts with existing API endpoint.");
                }
            }
        }

        $linkMultipleField = false;

        if (!empty($params['linkMultipleField'])) {
            $linkMultipleField = true;
        }

        $linkMultipleFieldForeign = false;

        if (!empty($params['linkMultipleFieldForeign'])) {
            $linkMultipleFieldForeign = true;
        }

        $audited = false;

        if (!empty($params['audited'])) {
            $audited = true;
        }

        $auditedForeign = false;

        if (!empty($params['auditedForeign'])) {
            $auditedForeign = true;
        }

        if ($linkType !== self::CHILDREN_TO_PARENT) {
            if (empty($entityForeign)) {
                throw new Error();
            }
        }

        if ($this->metadata->get("entityDefs.$entity.links.$link")) {
            throw new Conflict("Link $entity::$link already exists.");
        }

        if ($entityForeign && $this->metadata->get("entityDefs.$entityForeign.links.$linkForeign")) {
            throw new Conflict("Link $entityForeign::$linkForeign already exists.");
        }

        if ($this->metadata->get("entityDefs.$entity.fields.$link")) {
            throw new Conflict("Field $entity::$link already exists.");
        }

        if ($entityForeign && $this->metadata->get("entityDefs.$entityForeign.fields.$linkForeign")) {
            throw new Conflict("Field $entityForeign::$linkForeign already exists.");
        }

        if ($entity === $entityForeign) {
            if (
                $link === lcfirst($entity) ||
                $linkForeign === lcfirst($entity) ||
                $link === $linkForeign
            ) {
                throw new Conflict("Link names $entityForeign, $linkForeign conflict.");
            }
        }

        if ($linkForeign === lcfirst($entityForeign)) {
            throw new Conflict("Link $entityForeign::$linkForeign must not match entity type name.");
        }

        if ($link === lcfirst($entity)) {
            throw new Conflict("Link $entity::$link must not match entity type name.");
        }

        switch ($linkType) {
            case self::ONE_TO_ONE_RIGHT:
            case self::ONE_TO_ONE_LEFT:

                if (
                    $this->metadata->get('entityDefs.' . $entityForeign . '.fields.' . $linkForeign) ||
                    $this->metadata->get('entityDefs.' . $entityForeign . '.fields.' . $linkForeign . 'Id') ||
                    $this->metadata->get('entityDefs.' . $entityForeign . '.fields.' . $linkForeign . 'Name')
                ) {
                    throw new Conflict("Field $entityForeign::$linkForeign already exists.");
                }

                if (
                    $this->metadata->get('entityDefs.' . $entity . '.fields.' . $link) ||
                    $this->metadata->get('entityDefs.' . $entity . '.fields.' . $link . 'Id') ||
                    $this->metadata->get('entityDefs.' . $entity . '.fields.' . $link . 'Name')
                ) {
                    throw new Conflict("Field $entity::$link already exists.");
                }

                if ($linkType === self::ONE_TO_ONE_LEFT) {
                    $dataLeft = [
                        'fields' => [
                            $link => [
                                FieldParam::TYPE => FieldType::LINK_ONE,
                            ],
                        ],
                        'links' => [
                            $link => [
                                RelationParam::TYPE => Entity::HAS_ONE,
                                RelationParam::FOREIGN => $linkForeign,
                                RelationParam::ENTITY => $entityForeign,
                                'isCustom' => true,
                            ],
                        ],
                    ];

                    $dataRight = [
                        'fields' => [
                            $linkForeign => [
                                FieldParam::TYPE => FieldType::LINK,
                            ],
                        ],
                        'links' => [
                            $linkForeign => [
                                RelationParam::TYPE => Entity::BELONGS_TO,
                                RelationParam::FOREIGN => $link,
                                RelationParam::ENTITY => $entity,
                                'isCustom' => true,
                            ],
                        ],
                    ];
                } else {
                    $dataLeft = [
                        'fields' => [
                            $link => [
                                FieldParam::TYPE => FieldType::LINK,
                            ],
                        ],
                        'links' => [
                            $link => [
                                FieldParam::TYPE => Entity::BELONGS_TO,
                                RelationParam::FOREIGN => $linkForeign,
                                RelationParam::ENTITY => $entityForeign,
                                'isCustom' => true,
                            ],
                        ],
                    ];

                    $dataRight = [
                        'fields' => [
                            $linkForeign => [
                                FieldParam::TYPE => FieldType::LINK_ONE,
                            ],
                        ],
                        'links' => [
                            $linkForeign => [
                                FieldParam::TYPE => Entity::HAS_ONE,
                                RelationParam::FOREIGN => $link,
                                RelationParam::ENTITY => $entity,
                                'isCustom' => true,
                            ],
                        ],
                    ];
                }

                break;

            case self::ONE_TO_MANY:

                if (
                    $this->metadata->get('entityDefs.' . $entityForeign . '.fields.' . $linkForeign) ||
                    $this->metadata->get('entityDefs.' . $entityForeign . '.fields.' . $linkForeign . 'Id') ||
                    $this->metadata->get('entityDefs.' . $entityForeign . '.fields.' . $linkForeign . 'Name')
                ) {
                    throw new Conflict("Field $entityForeign::$linkForeign already exists.");
                }

                $dataLeft = [
                    'links' => [
                        $link => [
                            FieldParam::TYPE => Entity::HAS_MANY,
                            RelationParam::FOREIGN => $linkForeign,
                            RelationParam::ENTITY => $entityForeign,
                            'audited' => $auditedForeign,
                            'isCustom' => true,
                        ],
                    ],
                ];

                if ($linkMultipleField) {
                    $dataLeft['fields'] = [
                        $link => [
                            FieldParam::TYPE => FieldType::LINK_MULTIPLE,
                        ],
                    ];
                }

                $dataRight = [
                    'fields' => [
                        $linkForeign => [
                            FieldParam::TYPE => FieldType::LINK,
                        ],
                    ],
                    'links' => [
                        $linkForeign => [
                            FieldParam::TYPE => Entity::BELONGS_TO,
                            RelationParam::FOREIGN => $link,
                            'entity' => $entity,
                            'audited' => $audited,
                            'isCustom' => true,
                        ],
                    ],
                ];

                break;

            case self::MANY_TO_ONE:

                if (
                    $this->metadata->get('entityDefs.' . $entity . '.fields.' . $link) ||
                    $this->metadata->get('entityDefs.' . $entity . '.fields.' . $link . 'Id') ||
                    $this->metadata->get('entityDefs.' . $entity . '.fields.' . $link . 'Name')
                ) {
                    throw new Conflict("Field $entity::$link already exists.");
                }

                $dataLeft = [
                    'fields' => [
                        $link => [
                            FieldParam::TYPE => FieldType::LINK,
                        ],
                    ],
                    'links' => [
                        $link => [
                            RelationParam::TYPE => Entity::BELONGS_TO,
                            RelationParam::FOREIGN => $linkForeign,
                            'entity' => $entityForeign,
                            'audited' => $auditedForeign,
                            'isCustom' => true,
                        ],
                    ],
                ];

                $dataRight = [
                    'links' => [
                        $linkForeign => [
                            FieldParam::TYPE => Entity::HAS_MANY,
                            RelationParam::FOREIGN => $link,
                            'entity' => $entity,
                            'audited' => $audited,
                            'isCustom' => true,
                        ],
                    ],
                ];

                if ($linkMultipleFieldForeign) {
                    $dataRight['fields'] = [
                        $linkForeign => [
                            FieldParam::TYPE => FieldType::LINK_MULTIPLE,
                        ],
                    ];
                }

                break;

            case self::MANY_TO_MANY:
                $dataLeft = [
                    'links' => [
                        $link => [
                            RelationParam::TYPE => Entity::HAS_MANY,
                            RelationParam::RELATION_NAME => $relationName,
                            RelationParam::FOREIGN => $linkForeign,
                            'entity' => $entityForeign,
                            'audited' => $auditedForeign,
                            'isCustom' => true,
                        ],
                    ],
                ];

                if ($linkMultipleField) {
                    $dataLeft['fields'] = [
                        $link => [
                            FieldParam::TYPE => FieldType::LINK_MULTIPLE,
                        ],
                    ];
                }

                $dataRight = [
                    'links' => [
                        $linkForeign => [
                            RelationParam::TYPE => Entity::HAS_MANY,
                            RelationParam::RELATION_NAME => $relationName,
                            RelationParam::FOREIGN => $link,
                            'entity' => $entity,
                            'audited' => $audited,
                            'isCustom' => true,
                        ]
                    ]
                ];

                if ($linkMultipleFieldForeign) {
                    $dataRight['fields'] = [
                        $linkForeign => [
                            FieldParam::TYPE => FieldType::LINK_MULTIPLE,
                        ],
                    ];
                }

                if ($entityForeign == $entity) {
                    $dataLeft['links'][$link][RelationParam::MID_KEYS] = ['leftId', 'rightId'];
                    $dataRight['links'][$linkForeign][RelationParam::MID_KEYS] = ['rightId', 'leftId'];
                }

                break;

            case self::CHILDREN_TO_PARENT:
                $dataLeft = [
                    EntityParam::FIELDS => [
                        $link => [
                            FieldParam::TYPE => FieldType::LINK_PARENT,
                            'entityList' => $params['parentEntityTypeList'] ?? null,
                        ],
                    ],
                    'links' => [
                        $link => [
                            RelationParam::TYPE => Entity::BELONGS_TO_PARENT,
                            RelationParam::FOREIGN => $linkForeign,
                            'isCustom' => true,
                        ],
                    ],
                ];

                break;

            default:
                throw new BadRequest();
        }

        $this->metadata->set('entityDefs', $entity, $dataLeft);

        if ($entityForeign) {
            $this->metadata->set('entityDefs', $entityForeign, $dataRight);
        }

        $this->setLayouts($params);

        if ($linkType !== self::CHILDREN_TO_PARENT) {
            $this->setSelectFilters($params);
        }

        $this->metadata->save();

        $this->language->set($entity, 'fields', $link, $label);
        $this->language->set($entity, 'links', $link, $label);

        if ($entityForeign) {
            $this->language->set($entityForeign, 'fields', $linkForeign, $labelForeign);
            $this->language->set($entityForeign, 'links', $linkForeign, $labelForeign);
        }

        $this->language->save();

        if ($this->isLanguageNotBase()) {
            $this->baseLanguage->set($entity, 'fields', $link, $label);
            $this->baseLanguage->set($entity, 'links', $link, $label);

            if ($entityForeign) {
                $this->baseLanguage->set($entityForeign, 'fields', $linkForeign, $labelForeign);
                $this->baseLanguage->set($entityForeign, 'links', $linkForeign, $labelForeign);
            }

            $this->baseLanguage->save();
        }

        if ($linkType === self::CHILDREN_TO_PARENT) {
            $foreignLinkEntityTypeList = $params['foreignLinkEntityTypeList'] ?? null;

            if (is_array($foreignLinkEntityTypeList)) {
                $this->updateParentForeignLinks($entity, $link, $linkForeign, $foreignLinkEntityTypeList);
            }
        }

        $this->linkHookProcessor->processCreate($linkParams);

        $this->dataManager->rebuild();
    }

    /**
     * @param array{
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
     * @throws BadRequest
     * @throws Error
     */
    public function update(array $params): void
    {
        $entity = $params['entity'];
        $link = $params['link'];
        $entityForeign = $params['entityForeign'] ?? null;
        $linkForeign = $params['linkForeign'] ?? null;

        if (empty($link)) {
            throw new BadRequest();
        }

        if (empty($entity)) {
            throw new BadRequest();
        }

        $linkType = $this->metadata->get("entityDefs.$entity.links.$link.type");
        $isCustom = $this->metadata->get("entityDefs.$entity.links.$link.isCustom");

        if ($linkType !== Entity::BELONGS_TO_PARENT) {
            if (empty($entityForeign)) {
                throw new BadRequest();
            }

            if (empty($linkForeign)) {
                throw new BadRequest();
            }
        }

        if (
            $this->metadata->get("entityDefs.$entity.links.$link.type") == Entity::HAS_MANY &&
            $this->metadata->get("entityDefs.$entity.links.$link.isCustom")
        ) {
            if (array_key_exists('linkMultipleField', $params)) {
                $this->updateLinkMultiple($params['linkMultipleField'], $entity, $link);
            }
        }

        if (
            $this->metadata->get("entityDefs.$entityForeign.links.$linkForeign.type") == Entity::HAS_MANY &&
            $this->metadata->get("entityDefs.$entityForeign.links.$linkForeign.isCustom")
        ) {
            /** @var string $entityForeign */
            /** @var string $linkForeign */

            if (array_key_exists('linkMultipleFieldForeign', $params)) {
                $this->updateLinkMultiple($params['linkMultipleFieldForeign'], $entityForeign, $linkForeign);
            }
        }

        if (
            in_array($this->metadata->get("entityDefs.$entity.links.$link.type"), [
                Entity::HAS_MANY,
                Entity::HAS_CHILDREN,
            ])
        ) {
            if (array_key_exists('audited', $params)) {
                $audited = $params['audited'];

                $dataLeft = [
                    'links' => [
                        $link => [
                            "audited" => $audited,
                        ],
                    ],
                ];
                $this->metadata->set('entityDefs', $entity, $dataLeft);
                $this->metadata->save();
            }
        }

        if (
            $linkForeign &&
            in_array(
                $this->metadata->get("entityDefs.$entityForeign.links.$linkForeign.type"),
                [
                    Entity::HAS_MANY,
                    Entity::HAS_CHILDREN,
                ]
            )
        ) {
            /** @var string $entityForeign */

            if (array_key_exists('auditedForeign', $params)) {
                $auditedForeign = $params['auditedForeign'];

                $dataRight = [
                    'links' => [
                        $linkForeign => [
                            "audited" => $auditedForeign,
                        ],
                    ],
                ];

                $this->metadata->set('entityDefs', $entityForeign, $dataRight);
                $this->metadata->save();
            }
        }

        if ($linkType === Entity::BELONGS_TO_PARENT) {
            $parentEntityTypeList = $params['parentEntityTypeList'] ?? null;

            if (is_array($parentEntityTypeList)) {
                $data = [
                    'fields' => [
                        $link => [
                            'entityList' => $parentEntityTypeList,
                        ],
                    ],
                ];

                $this->metadata->set('entityDefs', $entity, $data);
                $this->metadata->save();
            }

            $foreignLinkEntityTypeList = $params['foreignLinkEntityTypeList'] ?? null;

            if ($linkForeign && is_array($foreignLinkEntityTypeList)) {
                $this->updateParentForeignLinks($entity, $link, $linkForeign, $foreignLinkEntityTypeList);
            }
        }

        $this->setLayouts($params);

        if ($linkType !== self::CHILDREN_TO_PARENT) {
            $this->setSelectFilters($params);
        }

        $this->metadata->save();

        $label = null;

        if (isset($params['label'])) {
            $label = $params['label'];
        }

        if ($label) {
            $this->language->set($entity, 'fields', $link, $label);
            $this->language->set($entity, 'links', $link, $label);
        }

        $labelForeign = null;

        if ($linkType !== Entity::BELONGS_TO_PARENT) {
            /** @var string $linkForeign */
            /** @var string $entityForeign */

            if (isset($params['labelForeign'])) {
                $labelForeign = $params['labelForeign'];
            }

            if ($labelForeign) {
                $this->language->set($entityForeign, 'fields', $linkForeign, $labelForeign);
                $this->language->set($entityForeign, 'links', $linkForeign, $labelForeign);
            }
        }

        $this->language->save();

        if ($isCustom) {
            if ($this->language->getLanguage() !== $this->baseLanguage->getLanguage()) {

                if ($label) {
                    $this->baseLanguage->set($entity, 'fields', $link, $label);
                    $this->baseLanguage->set($entity, 'links', $link, $label);
                }

                if ($labelForeign && $linkType !== Entity::BELONGS_TO_PARENT) {
                    /** @var string $linkForeign */
                    /** @var string $entityForeign */

                    $this->baseLanguage->set($entityForeign, 'fields', $linkForeign, $labelForeign);
                    $this->baseLanguage->set($entityForeign, 'links', $linkForeign, $labelForeign);
                }

                $this->baseLanguage->save();
            }
        }

        $this->dataManager->clearCache();
    }

    /**
     * @param array{
     *   entity?: string,
     *   link?: string,
     * } $params
     * @throws Error
     * @throws BadRequest
     */
    public function delete(array $params): void
    {
        $entity = $params['entity'] ?? null;
        $link = $params['link'] ?? null;

        if (!$this->metadata->get("entityDefs.$entity.links.$link.isCustom")) {
            throw new Error("Could not delete link $entity.$link. Not isCustom.");
        }

        if (empty($entity) || empty($link)) {
            throw new BadRequest();
        }

        $entityForeign = $this->metadata->get("entityDefs.$entity.links.$link.entity");
        $linkForeign = $this->metadata->get("entityDefs.$entity.links.$link.foreign");
        $linkType = $this->metadata->get("entityDefs.$entity.links.$link.type");

        if (!$this->metadata->get(['entityDefs', $entity, 'links', $link, 'isCustom'])) {
            throw new Error("Can't remove not custom link.");
        }

        if ($linkType === Entity::HAS_CHILDREN) {
            $this->metadata->delete('entityDefs', $entity, [
                "links.$link",
            ]);

            $this->metadata->save();

            $this->deleteFromLanguage($entity, 'links', $link);
            $this->saveLanguage();

            return;
        }

        if ($linkType === Entity::BELONGS_TO_PARENT) {
            $this->metadata->delete('entityDefs', $entity, [
                "fields.$link",
                "links.$link",
            ]);

            $this->metadata->delete('clientDefs', $entity, ["dynamicLogic.fields.$link"]);

            $this->metadata->save();

            if ($linkForeign) {
                $this->updateParentForeignLinks($entity, $link, $linkForeign, []);
            }

            $this->deleteFromLanguage($entity, 'fields', $link);
            $this->deleteFromLanguage($entity, 'links', $link);

            $this->saveLanguage();

            return;
        }

        if (empty($entityForeign) || empty($linkForeign)) {
            throw new BadRequest();
        }

        $foreignLinkType = $this->metadata->get(['entityDefs', $entityForeign, 'links', $linkForeign, 'type']);

        $type = null;

        if ($linkType === Entity::HAS_MANY && $foreignLinkType === Entity::HAS_MANY) {
            $type = LinkType::MANY_TO_MANY;
        } else if ($linkType === Entity::HAS_MANY && $foreignLinkType === Entity::BELONGS_TO) {
            $type = LinkType::ONE_TO_MANY;
        } else if ($linkType === Entity::BELONGS_TO && $foreignLinkType === Entity::HAS_MANY) {
            $type = LinkType::MANY_TO_ONE;
        } else if ($linkType === Entity::HAS_ONE && $foreignLinkType === Entity::BELONGS_TO) {
            $type = LinkType::ONE_TO_ONE_LEFT;
        } else if ($linkType === Entity::BELONGS_TO && $foreignLinkType === Entity::HAS_ONE) {
            $type = LinkType::ONE_TO_ONE_RIGHT;
        }

        $name = $this->metadata->get(['entityDefs', $entity, $link, RelationParam::RELATION_NAME]) ??
            $this->metadata->get(['entityDefs', $entityForeign, $linkForeign, RelationParam::RELATION_NAME]);

        $linkParams = null;

        if ($type) {
            $linkParams = LinkParams::createBuilder()
                ->setType($type)
                ->setName($name)
                ->setEntityType($entity)
                ->setForeignEntityType($entityForeign)
                ->setLink($link)
                ->setForeignLink($linkForeign)
                ->build();
        }

        $this->metadata->delete('clientDefs', $entity, ["dynamicLogic.fields.$link"]);
        $this->metadata->delete('clientDefs', $entityForeign, ["dynamicLogic.fields.$linkForeign"]);

        $this->metadata->delete('entityDefs', $entity, [
            "fields.$link",
            "links.$link",
        ]);

        $this->metadata->delete('entityDefs', $entityForeign, [
            "fields.$linkForeign",
            "links.$linkForeign",
        ]);

        $this->metadata->delete('clientDefs', $entity, ["relationshipPanels.$link"]);
        $this->metadata->delete('clientDefs', $entityForeign, ["relationshipPanels.$linkForeign"]);

        $this->metadata->save();

        if ($linkParams) {
            $this->linkHookProcessor->processDelete($linkParams);
        }

        $this->deleteFromLanguage($entity, 'fields', $link);
        $this->deleteFromLanguage($entity, 'links', $link);

        $this->deleteFromLanguage($entityForeign, 'fields', $linkForeign);
        $this->deleteFromLanguage($entityForeign, 'links', $linkForeign);

        $this->saveLanguage();

        $this->dataManager->clearCache();
    }

    /**
     * @param array{
     *   entity: string,
     *   link: string,
     *   entityForeign?: ?string,
     *   linkForeign?: ?string,
     *   layout?: string,
     *   layoutForeign?: string,
     * } $params
     */
    private function setLayouts(array $params): void
    {
        $this->setLayout($params['entity'], $params['link'], $params['layout'] ?? null);

        if (isset($params['entityForeign']) && isset($params['linkForeign'])) {
            $this->setLayout($params['entityForeign'], $params['linkForeign'], $params['layoutForeign'] ?? null);
        }
    }

    private function setLayout(string $entityType, string $link, ?string $layout): void
    {
        $this->metadata->set('clientDefs', $entityType, [
            'relationshipPanels' => [
                $link => [
                    'layout' => $layout,
                ]
            ]
        ]);
    }

    /**
     * @param array{
     *   entity: string,
     *   link: string,
     *   entityForeign?: ?string,
     *   linkForeign?: ?string,
     *   selectFilter?: string,
     *   selectFilterForeign?: string,
     * } $params
     */
    private function setSelectFilters(array $params): void
    {
        $this->setSelectFilter($params['entity'], $params['link'], $params['selectFilter'] ?? null);

        if (!isset($params['entityForeign']) || !isset($params['linkForeign'])) {
            return;
        }

        $this->setSelectFilter(
            $params['entityForeign'], $params['linkForeign'], $params['selectFilterForeign'] ?? null);
    }

    private function setSelectFilter(string $entityType, string $link, ?string $selectFilter): void
    {
        $this->metadata->set('clientDefs', $entityType, [
            'relationshipPanels' => [
                $link => [
                    'selectPrimaryFilterName' => $selectFilter,
                ]
            ]
        ]);
    }

    /**
     * @param string[] $foreignLinkEntityTypeList
     */
    private function updateParentForeignLinks(
        string $entityType,
        string $link,
        string $linkForeign,
        array $foreignLinkEntityTypeList
    ): void {

        $toCreateList = [];

        foreach ($foreignLinkEntityTypeList as $foreignEntityType) {
            $linkDefs = $this->metadata->get(['entityDefs', $foreignEntityType, 'links']) ?? [];

            foreach ($linkDefs as $kLink => $defs) {
                $kForeign = $defs['foreign'] ?? null;
                $kIsCustom = $defs['isCustom'] ?? false;
                $kEntity = $defs['entity'] ?? null;

                if (
                    $kForeign === $link && !$kIsCustom && $kEntity == $entityType
                ) {
                    continue 2;
                }

                if ($kLink == $linkForeign) {
                    if ($defs[RelationParam::TYPE] !== Entity::HAS_CHILDREN) {
                        continue 2;
                    }
                }
            }

            $toCreateList[] = $foreignEntityType;
        }

        /** @var string[] $entityTypeList */
        $entityTypeList = array_keys($this->metadata->get('entityDefs') ?? []);

        foreach ($entityTypeList as $itemEntityType) {
            $linkDefs = $this->metadata->get(['entityDefs', $itemEntityType, 'links']) ?? [];

            foreach ($linkDefs as $kLink => $defs) {
                $kForeign = $defs['foreign'] ?? null;
                $kIsCustom = $defs['isCustom'] ?? false;
                $kEntity = $defs['entity'] ?? null;

                if (
                    $kForeign === $link && $kIsCustom && $kEntity == $entityType &&
                    $defs[RelationParam::TYPE] == Entity::HAS_CHILDREN && $kLink === $linkForeign
                ) {
                    if (!in_array($itemEntityType, $toCreateList)) {
                        $this->metadata->delete('entityDefs', $itemEntityType, [
                            'links.' . $linkForeign,
                        ]);

                        $this->deleteFromLanguage($itemEntityType, 'links', $linkForeign);
                    }

                    break;
                }
            }
        }

        foreach ($toCreateList as $itemEntityType) {
            $this->metadata->set('entityDefs', $itemEntityType, [
                'links' => [
                    $linkForeign => [
                        RelationParam::TYPE => Entity::HAS_CHILDREN,
                        RelationParam::FOREIGN => $link,
                        RelationParam::ENTITY => $entityType,
                        'isCustom' => true,
                    ],
                ],
            ]);

            $label = $this->language->translate($entityType, 'scopeNamesPlural');

            $this->language->set($itemEntityType, 'links', $linkForeign, $label);

            if ($this->isLanguageNotBase()) {
                $this->baseLanguage->set($itemEntityType, 'links', $linkForeign, $label);
            }
        }

        $this->metadata->save();
        $this->saveLanguage();
    }

    private function deleteFromLanguage(string $scope, string $category, string $item): void
    {
        $this->language->delete($scope, $category, $item);

        if ($this->isLanguageNotBase()) {
            $this->baseLanguage->delete($scope, $category, $item);
        }
    }

    private function isLanguageNotBase(): bool
    {
        return $this->language->getLanguage() !== $this->baseLanguage->getLanguage();
    }

    private function isNameTooLong(string $name): bool
    {
        return strlen(Util::camelCaseToUnderscore($name)) > self::MAX_LINK_NAME_LENGTH;
    }

    private function saveLanguage(): void
    {
        $this->language->save();

        if ($this->isLanguageNotBase()) {
            $this->baseLanguage->save();
        }
    }

    private function isScopeCustom(string $scope): bool
    {
        return (bool) $this->metadata->get("scopes.$scope.isCustom");
    }

    private function isScopeCustomizable(string $scope): bool
    {
        if (!$this->metadata->get("scopes.$scope.customizable")) {
            return false;
        }

        if ($this->metadata->get("scopes.$scope.entityManager.relationships") === false) {
            return false;
        }

        return true;
    }

    /**
     * @param array{readOnly?: bool} $params
     * @throws Error
     */
    public function updateParams(string $entityType, string $link, array $params): void
    {
        $type = $this->metadata->get("entityDefs.$entityType.links.$link.type");

        $defs = [];

        if (
            in_array($type, [RelationType::HAS_MANY, RelationType::HAS_CHILDREN]) &&
            array_key_exists('readOnly', $params)
        ) {
            $defs['readOnly'] = $params['readOnly'];
        }

        $this->metadata->set('entityDefs', $entityType, [
            'links' => [
                $link => $defs,
            ],
        ]);

        $this->metadata->save();
        $this->dataManager->clearCache();
    }

    /**
     * @throws Error
     */
    public function resetToDefault(string $entityType, string $link): void
    {
        $this->metadata->delete('entityDefs', $entityType, [
            "links.$link.readOnly",
        ]);

        $this->metadata->save();
        $this->dataManager->clearCache();
    }

    private function updateLinkMultiple(bool $has, string $entityType, string $link): void
    {
        if ($has) {
            $data = [];

            $data['fields'] = [
                $link => [
                    FieldParam::TYPE => FieldType::LINK_MULTIPLE,
                ],
            ];

            $this->metadata->set('entityDefs', $entityType, $data);
        } else {
            $this->metadata->delete('entityDefs', $entityType, "fields.$link");
        }

        $this->metadata->save();
    }
}
