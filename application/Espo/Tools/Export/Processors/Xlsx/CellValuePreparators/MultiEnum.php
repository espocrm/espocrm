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

namespace Espo\Tools\Export\Processors\Xlsx\CellValuePreparators;

use Espo\Core\Utils\Json;
use Espo\Core\Utils\Language;
use Espo\ORM\Defs;
use Espo\Tools\Export\Processors\Xlsx\CellValuePreparator;
use Espo\Tools\Export\Processors\Xlsx\FieldHelper;

class MultiEnum implements CellValuePreparator
{
    public function __construct(
        private Defs $ormDefs,
        private Language $language,
        private FieldHelper $fieldHelper
    ) {}

    public function prepare(string $entityType, string $name, array $data): ?string
    {
        if (!array_key_exists($name, $data)) {
            return null;
        }

        $value = $data[$name];

        $list = Json::decode($value);

        if (!is_array($list)) {
            return null;
        }

        /** @var string[] $list */

        $fieldData = $this->fieldHelper->getData($entityType, $name);

        if (!$fieldData) {
            return $this->joinList($list);
        }

        $entityType = $fieldData->getEntityType();
        $field = $fieldData->getField();

        $translation = $this->ormDefs
            ->getEntity($entityType)
            ->getField($field)
            ->getParam('translation');

        if (!$translation) {
            return $this->joinList(
                array_map(
                    function ($item) use ($field, $entityType) {
                        return $this->language->translateOption($item, $field, $entityType);
                    },
                    $list
                )
            );
        }

        $map = $this->language->get($translation);

        if (!is_array($map)) {
            return $this->joinList($list);
        }

        return $this->joinList(
            array_map(
                function ($item) use ($map) {
                    return $map[$item] ?? $item;
                },
                $list
            )
        );
    }

    /**
     * @param string[] $list
     */
    private function joinList(array $list): string
    {
        return implode(', ', $list);
    }
}
