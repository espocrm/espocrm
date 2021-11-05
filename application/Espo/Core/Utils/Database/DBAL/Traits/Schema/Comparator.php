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

namespace Espo\Core\Utils\Database\DBAL\Traits\Schema;

use Doctrine\DBAL\{
    Schema\Column,
    Types\TextType,
};

trait Comparator
{
    /*
     * Fix problem with executing query for custom types
     */
    protected function espoFixTypeDiff(array &$changedProperties, Column $column1, Column $column2)
    {
        $column1DbType = $this->getColumnDbType($column1);
        $column2DbType = $this->getColumnDbType($column2);

        if ($column1DbType == $column2DbType) {
            return;
        }

        if (! $column1->getType() instanceof TextType) {
            $changedProperties[] = 'type';
            return;
        }

        $column1DbLength = $this->getColumnDbLength($column1);
        $column2DbLength = $this->getColumnDbLength($column2);

        if ($column1DbLength && $column2DbLength && $column2DbLength > $column1DbLength) {
            $changedProperties[] = 'type';
        }
    }
    /* Espo: end */

    private function getColumnDbType(Column $column)
    {
        $dbType = $column->getType()->getName();

        if (method_exists($column->getType(), 'getDbTypeName')) {
            $dbType = $column->getType()->getDbTypeName();
        }

        return strtoupper($dbType);
    }

    private function getColumnDbLength(Column $column)
    {
        $dbType = $this->getColumnDbType($column);

        switch ($dbType) {
            case 'LONGTEXT':
                return 4294967295;
        }

        $constName = '\\Doctrine\\DBAL\\Platforms\\MySQLPlatform::LENGTH_LIMIT_' . $dbType;

        if (defined($constName)) {
            return constant($constName);
        }
    }
}
