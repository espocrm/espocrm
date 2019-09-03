<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Utils\Database\DBAL\Schema;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Schema\SchemaException;

class Table extends \Doctrine\DBAL\Schema\Table
{
    protected $_quoted = true;

    /**
     * @param string $columnName
     * @param string $typeName
     * @param array  $options
     *
     * @return \Doctrine\DBAL\Schema\Column
     */
    public function addColumn($columnName, $typeName, array $options=array())
    {
        $column = new Column($columnName, Type::getType($typeName), $options);

        $this->_addColumn($column);

        return $column;
    }

    public function addIndex(array $columnNames, $indexName = null, array $flags = array())
    {
        if($indexName == null) {
            $indexName = $this->_generateIdentifierName(
                array_merge(array($this->getName()), $columnNames), "idx", $this->_getMaxIdentifierLength()
            );
        }

        return $this->_createIndex($columnNames, $indexName, false, false, $flags);
    }

    private function _createIndex(array $columnNames, $indexName, $isUnique, $isPrimary, array $flags = array())
    {
        if (preg_match('(([^a-zA-Z0-9_]+))', $indexName)) {
            throw SchemaException::indexNameInvalid($indexName);
        }

        foreach ($columnNames as $columnName => $indexColOptions) {
            if (is_numeric($columnName) && is_string($indexColOptions)) {
                $columnName = $indexColOptions;
            }

            if ( ! $this->hasColumn($columnName)) {
                throw SchemaException::columnDoesNotExist($columnName, $this->_name);
            }
        }

        $this->_addIndex(new Index($indexName, $columnNames, $isUnique, $isPrimary, $flags));

        return $this;
    }
}
