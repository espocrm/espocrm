<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Utils\Config;
use Espo\Core\Select\Text\FullTextSearch\Mode;

use Espo\ORM\Query\Part\Expression\Util as ExpressionUtil;
use Espo\ORM\Query\Part\Expression;

class FullTextSearchDataComposer
{
    private string $entityType;

    private Config $config;

    private MetadataProvider $metadataProvider;

    /**
     * @var array<Mode::*,string>
     */
    private array $functionMap = [
        Mode::BOOLEAN => 'MATCH_BOOLEAN',
        Mode::NATURAL_LANGUAGE => 'MATCH_NATURAL_LANGUAGE',
    ];

    public function __construct(
        string $entityType,
        Config $config,
        MetadataProvider $metadataProvider
    ) {
        $this->entityType = $entityType;
        $this->config = $config;
        $this->metadataProvider = $metadataProvider;
    }

    public function compose(string $filter, FullTextSearchDataComposerParams $params): ?FullTextSearchData
    {
        if ($this->config->get('fullTextSearchDisabled')) {
            return null;
        }

        $columnList = $this->metadataProvider->getFullTextSearchColumnList($this->entityType) ?? [];

        if (!count($columnList)) {
            return null;
        }

        $fieldList = [];

        foreach ($this->getTextFilterFieldList() as $field) {
            if (strpos($field, '.') !== false) {
                continue;
            }

            if ($this->metadataProvider->isFieldNotStorable($this->entityType, $field)) {
                continue;
            }

            if (!$this->metadataProvider->isFullTextSearchSupportedForField($this->entityType, $field)) {
                continue;
            }

            $fieldList[] = $field;
        }

        if (!count($fieldList)) {
            return null;
        }

        if (
            substr_count($filter, '\'') % 2 !== 0 ||
            substr_count($filter, '"') % 2 !== 0
        ) {
            return null;
        }

        if ($params->isAuxiliaryUse() && mb_strpos($filter, '@') !== false) {
            return null;
        }

        $preparedFilter = $this->prepareFilter($filter, $params);

        $mode = Mode::BOOLEAN;

        if (
            $params->isAuxiliaryUse() && mb_strpos($preparedFilter, '*') === false
            ||
            mb_strpos($preparedFilter, ' ') === false &&
            mb_strpos($preparedFilter, '+') === false &&
            mb_strpos($preparedFilter, '-') === false &&
            mb_strpos($preparedFilter, '*') === false
        ) {
            $mode = Mode::NATURAL_LANGUAGE;
        }

        $argumentList = array_merge(
            array_map(
                function ($item) {
                    return Expression::column($item);
                },
                $columnList
            ),
            [$preparedFilter]
        );

        $function = $this->functionMap[$mode];

        $expression = ExpressionUtil::composeFunction($function, ...$argumentList);

        return new FullTextSearchData(
            $expression,
            $fieldList,
            $columnList,
            $mode
        );
    }

    private function prepareFilter(string $filter, FullTextSearchDataComposerParams $params): string
    {
        $filter = str_replace(['(', ')'], '', $filter);

        $filter = str_replace('"*', '"', $filter);
        $filter = str_replace('*"', '"', $filter);

        while (strpos($filter, '**')) {
            $filter = trim(
                str_replace('**', '*', $filter)
            );
        }

        while (mb_substr($filter, -2) === ' *') {
            $filter = trim(
                mb_substr($filter, 0, mb_strlen($filter) - 2)
            );
        }

        if ($params->isAuxiliaryUse()) {
            $filter = str_replace('%', '', $filter);
        }

        return $filter;
    }

    /**
     * @return string[]
     */
    private function getTextFilterFieldList(): array
    {
        return $this->metadataProvider->getTextFilterAttributeList($this->entityType) ?? ['name'];
    }
}
