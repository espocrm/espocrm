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

$config = $installer->getConfig();

$fields = array(
    'dateFormat' =>array (
        'default' => $config->get('dateFormat', ''),
    ),
    'timeFormat' => array(
        'default'=> $config->get('timeFormat', ''),
    ),
    'timeZone' => array(
        'default'=> $config->get('timeZone', 'UTC'),
    ),
    'weekStart' => array(
        'default'=> $config->get('weekStart', 0),
    ),
    'defaultCurrency' => array(
        'default' => $config->get('defaultCurrency', 'USD'),
    ),
    'thousandSeparator' => array(
        'default' => $config->get('thousandSeparator', ','),
    ),
    'decimalMark' =>array(
        'default' => $config->get('decimalMark', '.'),
    ),
    'language' => array(
        'default'=> (!empty($_SESSION['install']['user-lang'])) ? $_SESSION['install']['user-lang'] : $config->get('language', 'en_US'),
    ),
);

foreach ($fields as $fieldName => $field) {
    if (isset($_SESSION['install'][$fieldName])) {
        $fields[$fieldName]['value'] = $_SESSION['install'][$fieldName];
    } else {
        $fields[$fieldName]['value'] = isset($field['default']) ? $field['default'] : '';
    }
}

$smarty->assign('fields', $fields);
