<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace Espo\Core\Utils\Database\Orm\FieldConverters;

use Doctrine\DBAL\Types\Types;
use Espo\Core\Utils\Database\Orm\Defs\AttributeDefs;
use Espo\Core\Utils\Database\Orm\Defs\EntityDefs;
use Espo\Core\Utils\Database\Orm\FieldConverter;
use Espo\ORM\Defs\FieldDefs;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\ORM\Type\AttributeType;

class Decimal implements FieldConverter
{
    private const DEFAULT_PRECISION = 13;
    private const DEFAULT_SCALE = 4;

    public function convert(FieldDefs $fieldDefs, string $entityType): EntityDefs
    {
        $name = $fieldDefs->getName();

        $dbType = $fieldDefs->getParam(FieldParam::DB_TYPE) ?? Types::DECIMAL;
        $precision = $fieldDefs->getParam(FieldParam::PRECISION) ?? self::DEFAULT_PRECISION;
        $scale = $fieldDefs->getParam(FieldParam::SCALE) ?? self::DEFAULT_SCALE;

        $defs = AttributeDefs::create($name)
            ->withType(AttributeType::VARCHAR)
            ->withDbType($dbType)
            ->withParam(AttributeParam::PRECISION, $precision)
            ->withParam(AttributeParam::SCALE, $scale);

        return EntityDefs::create()
            ->withAttribute($defs);
    }
}
