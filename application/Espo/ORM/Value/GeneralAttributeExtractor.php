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

namespace Espo\ORM\Value;

use stdClass;

class GeneralAttributeExtractor
{
    /** @var AttributeExtractorFactory<object> */
    private AttributeExtractorFactory $factory;

    /**
     * @var array<string, AttributeExtractor<object>>
     */
    private $cache = [];

    /**
     * @param AttributeExtractorFactory<object> $factory
     */
    public function __construct(AttributeExtractorFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Extracts attributes from a value object.
     */
    public function extract(string $entityType, string $field, ?object $value): stdClass
    {
        $extractor = $this->getExtractor($entityType, $field);

        if (is_null($value)) {
            return $extractor->extractFromNull($field);
        }

        return $extractor->extract($value, $field);
    }

    /**
     * @return AttributeExtractor<object>
     */
    private function getExtractor(string $entityType, string $field): AttributeExtractor
    {
        $key = $entityType . '_' . $field;

        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = $this->factory->create($entityType, $field);
        }

        return $this->cache[$key];
    }
}
