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

Espo.define('crm:views/record/row-actions/activities', 'views/record/row-actions/relationship', function (Dep) {

    return Dep.extend({

        getActionList: function () {
            var list = [{
                action: 'viewRelated',
                label: 'View',
                data: {
                    id: this.model.id
                }
            }];
            if (this.options.acl.edit) {
                list.push({
                    action: 'editRelated',
                    label: 'Edit',
                    data: {
                        id: this.model.id
                    }
                });
                if (this.model.name == 'Meeting' || this.model.name == 'Call') {
                    list.push({
                        action: 'setHeld',
                        html: this.translate('Set Held', 'labels', 'Meeting'),
                        data: {
                            id: this.model.id
                        }
                    });
                    list.push({
                        action: 'setNotHeld',
                        html: this.translate('Set Not Held', 'labels', 'Meeting'),
                        data: {
                            id: this.model.id
                        }
                    });
                }
                list.push({
                    action: 'removeRelated',
                    label: 'Remove',
                    data: {
                        id: this.model.id
                    }
                });

            }
            return list;
        }

    });

});

