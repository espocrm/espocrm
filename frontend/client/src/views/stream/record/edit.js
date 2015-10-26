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

Espo.define('views/stream/record/edit', 'views/record/base', function (Dep) {

    return Dep.extend({

        template: 'stream/record/edit',

        dependencyDefs: {
            'targetType': {
                map: {
                    'users': [
                        {
                            action: 'hide',
                            fields: ['teams']
                        },
                        {
                            action: 'show',
                            fields: ['users']
                        },
                        {
                            action: 'setNotRequired',
                            fields: ['teams']
                        },
                        {
                            action: 'setRequired',
                            fields: ['users']
                        }
                    ],
                    'teams': [
                        {
                            action: 'hide',
                            fields: ['users']
                        },
                        {
                            action: 'show',
                            fields: ['teams']
                        },
                        {
                            action: 'setRequired',
                            fields: ['teams']
                        },
                        {
                            action: 'setNotRequired',
                            fields: ['users']
                        }
                    ]
                },
                default: [
                    {
                        action: 'hide',
                        fields: ['teams', 'users']
                    },
                    {
                        action: 'setNotRequired',
                        fields: ['teams', 'users']
                    }
                ]
            }
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            var optionList = ['self'];

            this.model.set('targetType', 'self');

            var assignmentPermission = this.getAcl().get('assignmentPermission');

            if (assignmentPermission === true || assignmentPermission === 'team') {
                optionList.push('users');
                optionList.push('teams');
            }
            if (assignmentPermission === true || assignmentPermission === 'all') {
                optionList.push('all');
            }

            this.createField('targetType', 'views/fields/enum', {
                required: true,
                options: optionList
            });

            this.createField('users', 'views/fields/users', {});
            this.createField('teams', 'views/fields/teams', {});
        }

    });

});