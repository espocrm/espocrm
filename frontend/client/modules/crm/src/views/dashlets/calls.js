/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('Crm:Views.Dashlets.Calls', 'Views.Dashlets.Abstract.RecordList', function (Dep) {

    return Dep.extend({

        name: 'Calls',

        scope: 'Call',

        defaultOptions: {
            sortBy: 'dateStart',
            asc: true,
            displayRecords: 5,
            expandedLayout: {
                rows: [
                    [
                        {
                            name: 'name',
                            link: true,
                        }
                    ],
                    [
                        {
                            name: 'dateStart'
                        }
                    ]
                ]
            },
            searchData: {
                bool: {
                    onlyMy: true,
                },
                primary: 'planned',
                advanced: {
                    '1': {
                        type: 'or',
                        value: {
                            '1': {
                                type: 'today',
                                field: 'dateStart',
                                dateTime: true
                            },
                            '2': {
                                type: 'future',
                                field: 'dateEnd',
                                dateTime: true
                            }
                        }
                    }
                }
            },
        },

    });
});

