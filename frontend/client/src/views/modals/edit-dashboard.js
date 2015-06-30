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


Espo.define('Views.Modals.EditDashboard', ['Views.Modal', 'Model'], function (Dep, Model) {

    return Dep.extend({

        cssName: 'edit-dashboard',

        template: 'modals.edit-dashboard',

        data: function () {
            return {

            };
        },

        events: {
            'click button.add': function (e) {
                var name = $(e.currentTarget).data('name');
                this.getParentView().addDashlet(name);
                this.close();
            },
        },

        setup: function () {
            this.buttons = [
                {
                    name: 'save',
                    label: 'Save',
                    style: 'primary',
                    onClick: function (dialog) {
                        if (this.save()) {
                            dialog.close();
                        }
                    }.bind(this)
                },
                {
                    name: 'cancel',
                    label: 'Cancel',
                    onClick: function (dialog) {
                        dialog.close();
                    }
                }
            ];

            var dashboardLayout = this.options.dashboardLayout || [];

            var dashboardTabList = [];
            dashboardLayout.forEach(function (item) {
                if (item.name) {
                    dashboardTabList.push(item.name);
                }
            }, this);

            var model = new Model();
            model.name = 'Preferences';

            model.set('dashboardTabList', dashboardTabList);
            this.createView('dashboardTabList', 'Fields.Array', {
                el: this.options.el + ' .field-dashboardTabList',
                defs: {
                    name: 'dashboardTabList',
                    params: {
                        required: true,
                        noEmptyString: true,
                    }
                },
                mode: 'edit',
                model: model
            });

            this.header = this.translate('Edit Dashboard');

            this.dashboardLayout = this.options.dashboardLayout;
        },

        save: function () {
            var dashboardTabListView = this.getView('dashboardTabList');
            dashboardTabListView.fetchToModel();
            if (dashboardTabListView.validate()) {
                return;
            }

            var attributes = {};
            attributes.dashboardTabList = dashboardTabListView.model.get('dashboardTabList');

            this.trigger('after:save', attributes);
            return true;
        },
    });
});


