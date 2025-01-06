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

namespace Espo\Tools\Export\Format\Xlsx;

use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Entity;
use Espo\Tools\Export\Params;
use Espo\Tools\Export\Processor;
use Espo\Tools\Export\ProcessorParamsHandler;

class ParamsHandler implements ProcessorParamsHandler
{
    public function __construct(
        private Metadata $metadata
    ) {}

    public function handle(Params $params, Processor\Params $processorParams): Processor\Params
    {
        $fieldList = $processorParams->getFieldList();

        if ($fieldList === null) {
            return $processorParams;
        }

        $fieldList = $this->filterFieldList($params->getEntityType(), $fieldList, $params->allFields());

        $attributeList = $processorParams->getAttributeList();

        $this->addAdditionalAttributes($params->getEntityType(), $attributeList, $fieldList);

        return $processorParams
            ->withAttributeList($attributeList)
            ->withFieldList($fieldList);
    }

    /**
     * @param string[] $fieldList
     * @return string[]
     */
    private function filterFieldList(string $entityType, array $fieldList, bool $exportAllFields): array
    {
        if ($exportAllFields) {
            foreach ($fieldList as $i => $field) {
                $type = $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'type']);

                if (in_array($type, [FieldType::LINK_MULTIPLE, FieldType::ATTACHMENT_MULTIPLE])) {
                    unset($fieldList[$i]);
                }
            }
        }

        return array_values($fieldList);
    }

    /**
     * @param string[] $attributeList
     * @param string[] $fieldList
     */
    private function addAdditionalAttributes(string $entityType, array &$attributeList, array $fieldList): void
    {
        $linkList = [];

        if (!in_array('id', $attributeList)) {
            $attributeList[] = 'id';
        }

        $linkDefs = $this->metadata->get(['entityDefs', $entityType, 'links']) ?? [];

        foreach ($linkDefs as $link => $defs) {
            $linkType = $defs['type'] ?? null;

            if (!$linkType) {
                continue;
            }

            if ($linkType === Entity::BELONGS_TO_PARENT) {
                $linkList[] = $link;

                continue;
            }

            if ($linkType === Entity::BELONGS_TO && !empty($defs[RelationParam::NO_JOIN])) {
                if ($this->metadata->get(['entityDefs', $entityType, 'fields', $link])) {
                    $linkList[] = $link;
                }
            }
        }

        foreach ($linkList as $item) {
            if (in_array($item, $fieldList) && !in_array($item . 'Name', $attributeList)) {
                $attributeList[] = $item . 'Name';
            }
        }

        foreach ($fieldList as $field) {
            $type = $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'type']);

            if ($type === FieldType::CURRENCY_CONVERTED) {
                if (!in_array($field, $attributeList)) {
                    $attributeList[] = $field;
                }
            }
        }
    }
}
