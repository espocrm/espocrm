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

namespace Espo\Core\Utils\Database\Schema\EntityDefsModifiers;

use Espo\Core\Utils\Database\Orm\Defs\AttributeDefs;
use Espo\Core\Utils\Database\Orm\Defs\EntityDefs;
use Espo\Core\Utils\Database\Schema\EntityDefsModifier;
use Espo\ORM\Defs\EntityDefs as OrmEntityDefs;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Type\AttributeType;

/**
 * A single JSON column instead of multiple field columns.
 */
class JsonData implements EntityDefsModifier
{
    public function modify(OrmEntityDefs $entityDefs): EntityDefs
    {
        $sourceIdAttribute = $entityDefs->getAttribute('id');

        $idAttribute = AttributeDefs::create('id')
            ->withType(AttributeType::ID);

        $length = $sourceIdAttribute->getLength();
        $dbType = $sourceIdAttribute->getParam(AttributeParam::DB_TYPE);

        if ($length) {
            $idAttribute = $idAttribute->withLength($length);
        }

        if ($dbType) {
            $idAttribute = $idAttribute->withDbType($dbType);
        }

        return EntityDefs::create()
            ->withAttribute($idAttribute)
            ->withAttribute(
                AttributeDefs::create('data')
                    ->withType(AttributeType::JSON_OBJECT)
            );
    }
}
