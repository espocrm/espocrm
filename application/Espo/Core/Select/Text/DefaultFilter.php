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

namespace Espo\Core\Select\Text;

use Espo\Core\Exceptions\Error;

use Espo\Core\Select\Text\MetadataProvider;
use Espo\Core\Select\Text\Filter\Data;
use Espo\Core\Select\Text\ConfigProvider;

use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use Espo\ORM\Query\Part\Where\OrGroup;
use Espo\ORM\Query\Part\Where\OrGroupBuilder;
use Espo\ORM\Query\Part\Where\Comparison as Cmp;
use Espo\ORM\Query\Part\Expression as Expr;

use Espo\ORM\Entity;

class DefaultFilter implements Filter
{
    private $entityType;

    private $metadataProvider;

    private $config;

    public function __construct(
        string $entityType,
        MetadataProvider $metadataProvider,
        ConfigProvider $config
    ) {
        $this->entityType = $entityType;
        $this->metadataProvider = $metadataProvider;
        $this->config = $config;
    }

    public function apply(QueryBuilder $queryBuilder, Data $data): void
    {
        $orGroupBuilder = OrGroup::createBuilder();

        foreach ($data->getAttributeList() as $attribute) {
            $this->applyAttribute($queryBuilder, $orGroupBuilder, $attribute, $data);
        }

        if ($data->getFullTextSearchWhereItem()) {
            $orGroupBuilder->add(
                $data->getFullTextSearchWhereItem()
            );
        }

        $orGroup = $orGroupBuilder->build();

        if ($orGroup->getItemCount() === 0) {
            $queryBuilder->where(['id' => null]);

            return;
        }

        $queryBuilder->where($orGroup);
    }

    /**
     * @todo AttributeFilterFactory.
     */
    private function applyAttribute(
        QueryBuilder $queryBuilder,
        OrGroupBuilder $orGroupBuilder,
        string $attribute,
        Data $data
    ): void {

        $filter = $data->getFilter();
        $skipWildcards = $data->skipWildcards();

        $attributeType = null;

        if (strpos($attribute, '.') !== false) {
            list($link, $foreignField) = explode('.', $attribute);

            $foreignEntityType = $this->metadataProvider->getRelationEntityType($this->entityType, $link);

            if (!$foreignEntityType) {
                throw new Error("Bad relation in text filter field '{$attribute}'.");
            }

            if ($this->metadataProvider->getRelationType($this->entityType, $link) === Entity::HAS_MANY) {
                $queryBuilder->distinct();
            }

            $queryBuilder->leftJoin($link);

            $attributeType = $this->metadataProvider->getAttributeType($foreignEntityType, $foreignField);
        }
        else {
            $attributeType = $this->metadataProvider->getAttributeType($this->entityType, $attribute);

            if ($attributeType === Entity::FOREIGN) {
                $link = $this->metadataProvider->getAttributeRelationParam($this->entityType, $attribute);

                if ($link) {
                    $queryBuilder->leftJoin($link);
                }
            }
        }

        if ($attributeType === Entity::INT) {
            if (is_numeric($filter)) {
                $orGroupBuilder->add(
                    Cmp::equal(
                        Expr::column($attribute),
                        intval($filter)
                    )
                );
            }

            return;
        }

        if (!$skipWildcards) {
            if ($this->checkWhetherToUseContains($attribute, $filter, $attributeType)) {
                $expression = '%' . $filter . '%';
            }
            else {
                $expression = $filter . '%';
            }
        }
        else {
            $expression = $filter;
        }

        $orGroupBuilder->add(
            Cmp::like(
                Expr::column($attribute),
                $expression
            )
        );
    }

    private function checkWhetherToUseContains(string $attribute, string $filter, string $attributeType): bool
    {
        if (mb_strlen($filter) < $this->config->getMinLengthForContentSearch()) {
            return false;
        }

        if ($attributeType === Entity::TEXT) {
            return true;
        }

        if (
            in_array(
                $attribute,
                $this->metadataProvider->getUseContainsAttributeList($this->entityType)
            )
        ) {
            return true;
        }

        if (
            $attributeType === Entity::VARCHAR &&
            $this->config->useContainsForVarchar()
        ) {
            return true;
        }

        return false;
    }
}
