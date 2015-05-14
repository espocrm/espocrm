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

Espo.define('Views.Record.RowActions.Default', 'View', function (Dep) {

    return Dep.extend({

        template: 'record.row-actions.default',


        afterRender: function () {
            var $dd = this.$el.find('button[data-toggle="dropdown"]').parent();

            $dd.on('show.bs.dropdown', function () {
                this.$el.closest('.list-row').addClass('active');
            }.bind(this));
            $dd.on('hide.bs.dropdown', function () {
                this.$el.closest('.list-row').removeClass('active');
            }.bind(this));
        },

        getActions: function () {
            var list = [{
                action: 'quickView',
                label: 'View',
                data: {
                    id: this.model.id
                }
            }];
            if (this.options.acl.edit) {
                list = list.concat([
                    {
                        action: 'quickEdit',
                        label: 'Edit',
                        data: {
                            id: this.model.id
                        }
                    },
                    {
                        action: 'quickRemove',
                        label: 'Remove',
                        data: {
                            id: this.model.id
                        }
                    }
                ]);
            }
            return list;
        },

        data: function () {
            return {
                acl: this.options.acl,
                actions: this.getActions(),
                scope: this.model.name
            };
        }
    });

});


