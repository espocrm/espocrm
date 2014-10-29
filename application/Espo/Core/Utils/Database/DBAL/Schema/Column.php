<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/
namespace Espo\Core\Utils\Database\DBAL\Schema;

class Column extends
    \Doctrine\DBAL\Schema\Column
{

    /**
     * @var boolean
     */
    protected $_notnull = false;

    /**
     * @var boolean
     */
    protected $_unique = false;

    /**
     * @return boolean
     */
    public function getUnique()
    {
        return $this->_unique;
    }

    /**
     * @param boolean $unique
     *
     * @return \Doctrine\DBAL\Schema\Column
     */
    public function setUnique($unique)
    {
        $this->_unique = (bool)$unique;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_merge(array(
            'name' => $this->_name,
            'type' => $this->_type,
            'default' => $this->_default,
            'notnull' => $this->_notnull,
            'length' => $this->_length,
            'precision' => $this->_precision,
            'scale' => $this->_scale,
            'fixed' => $this->_fixed,
            'unsigned' => $this->_unsigned,
            'autoincrement' => $this->_autoincrement,
            'unique' => $this->_unique,
            'columnDefinition' => $this->_columnDefinition,
            'comment' => $this->_comment,
        ), $this->_platformOptions, $this->_customSchemaOptions);
    }
}