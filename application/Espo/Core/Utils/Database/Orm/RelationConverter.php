<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Utils\Database\Orm;

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Database\Orm\LinkConverters\BelongsTo;
use Espo\Core\Utils\Database\Orm\LinkConverters\BelongsToParent;
use Espo\Core\Utils\Database\Orm\LinkConverters\HasChildren;
use Espo\Core\Utils\Database\Orm\LinkConverters\HasMany;
use Espo\Core\Utils\Database\Orm\LinkConverters\HasOne;
use Espo\Core\Utils\Database\Orm\LinkConverters\ManyMany;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Util;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Defs\RelationDefs;
use Espo\ORM\Type\AttributeType;
use Espo\ORM\Type\RelationType;

use RuntimeException;

class RelationConverter
{
    private const DEFAULT_VARCHAR_LENGTH = 255;

    /** @var string[] */
    private $allowedParams = [
        'relationName',
        'conditions',
        'additionalColumns',
        'midKeys',
        'noJoin',
        'indexes',
    ];

    public function __construct(
        private Metadata $metadata,
        private InjectableFactory $injectableFactory,
        private Log $log
    ) {}

    /**
     * @param string $name
     * @param array<string, mixed> $params
     * @param string $entityType
     * @param array<string, mixed> $ormMetadata
     * @return ?array<string, mixed>
     */
    public function process(string $name, array $params, string $entityType, array $ormMetadata): ?array
    {
        $foreignEntityType = $params['entity'] ?? null;
        $foreignLinkName = $params['foreign'] ?? null;

        /** @var ?array<string, mixed> $foreignParams */
        $foreignParams = $foreignEntityType && $foreignLinkName ?
            $this->metadata->get(['entityDefs', $foreignEntityType, 'links', $foreignLinkName]) :
            null;

        /** @var ?string $relationshipName */
        $relationshipName = $params['relationName'] ?? null;

        if ($relationshipName) {
            $relationshipName = lcfirst($relationshipName);
            $params['relationName'] = $relationshipName;
        }

        $linkType = $params['type'] ?? null;
        $foreignLinkType = $foreignParams ? $foreignParams['type'] : null;

        if (!$linkType) {
            $this->log->warning("Link {$entityType}.{$name} has no type.");

            return null;
        }

        $params['hasField'] = (bool) $this->metadata
            ->get(['entityDefs', $entityType, 'fields', $name]);

        $relationDefs = RelationDefs::fromRaw($params, $name);

        $converter = $this->createLinkConverter($relationshipName, $linkType, $foreignLinkType);

        $convertedEntityDefs = $converter->convert($relationDefs, $entityType);

        $raw = $convertedEntityDefs->toAssoc();

        if (isset($raw['relations'][$name])) {
            $this->mergeAllowedParams($raw['relations'][$name], $params, $foreignParams ?? []);
            $this->correct($raw['relations'][$name]);
        }

        return [$entityType => $raw];
    }

    private function createLinkConverter(?string $relationship, string $type, ?string $foreignType): LinkConverter
    {
        $className = $this->getLinkConverterClassName($relationship, $type, $foreignType);

        return $this->injectableFactory->create($className);
    }

    /**
     * @return class-string<LinkConverter>
     */
    private function getLinkConverterClassName(?string $relationship, string $type, ?string $foreignType): string
    {
        if ($relationship) {
            /** @var class-string<LinkConverter> $className */
            $className = $this->metadata->get(['app', 'relationships', $relationship, 'converterClassName']);

            if ($className) {
                return $className;
            }
        }

        if ($type === RelationType::HAS_MANY && $foreignType === RelationType::HAS_MANY) {
            return ManyMany::class;
        }

        if ($type === RelationType::HAS_MANY) {
            return HasMany::class;
        }

        if ($type === RelationType::HAS_CHILDREN) {
            return HasChildren::class;
        }

        if ($type === RelationType::HAS_ONE) {
            return HasOne::class;
        }

        if ($type === RelationType::BELONGS_TO) {
            return BelongsTo::class;
        }

        if ($type === RelationType::BELONGS_TO_PARENT) {
            return BelongsToParent::class;
        }

        throw new RuntimeException("Unsupported link type '{$type}'.");
    }

    /**
     * @param array<string, mixed> $relationDefs
     * @param array<string, mixed> $params
     * @param array<string, mixed> $foreignParams
     */
    private function mergeAllowedParams(array &$relationDefs, array $params, array $foreignParams): void
    {
        foreach ($this->allowedParams as $name) {
            $additionalParam = $this->getAllowedParam($name, $params, $foreignParams);

            if ($additionalParam === null) {
                continue;
            }

            $relationDefs[$name] = $additionalParam;
        }
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $foreignParams
     * @return array<string, mixed>|scalar|null
     */
    private function getAllowedParam(string $name, array $params, array $foreignParams): mixed
    {
        $value = $params[$name] ?? null;
        $foreignValue = $foreignParams[$name] ?? null;

        if ($value !== null && $foreignValue !== null) {
            if (!empty($value) && !is_array($value)) {
                return $value;
            }

            if (!empty($foreignValue) && !is_array($foreignValue)) {
                return $foreignValue;
            }

            /** @var array<int|string, mixed> $value */
            /** @var array<int|string, mixed> $foreignValue */

            /** @var array<string, mixed> */
            return Util::merge($value, $foreignValue);
        }

        if (isset($value)) {
            return $value;
        }

        if (isset($foreignValue)) {
            return $foreignValue;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $relationDefs
     */
    private function correct(array &$relationDefs): void
    {
        if (!isset($relationDefs['additionalColumns'])) {
            return;
        }

        /** @var array<string, array<string, mixed>> $additionalColumns */
        $additionalColumns = &$relationDefs['additionalColumns'];

        foreach ($additionalColumns as &$columnDefs) {
            $columnDefs['type'] ??= AttributeType::VARCHAR;

            if (
                $columnDefs['type'] === AttributeType::VARCHAR &&
                !isset($columnDefs['len'])
            ) {
                $columnDefs['len'] = self::DEFAULT_VARCHAR_LENGTH;
            }
        }

        $relationDefs['additionalColumns'] = $additionalColumns;
    }
}
