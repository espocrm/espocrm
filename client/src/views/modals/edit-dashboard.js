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


Espo.define('views/modals/edit-dashboard', ['views/modal', 'model'], function (Dep, Model) {

    return Dep.extend({

        cssName: 'edit-dashboard',

        template: 'modals/edit-dashboard',

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
            this.buttonList = [
                {
                    name: 'save',
                    label: 'Save',
                    style: 'primary'
                },
                {
                    name: 'cancel',
                    label: 'Cancel'
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
            this.createView('dashboardTabList', 'views/preferences/fields/dashboard-tab-list', {
                el: this.options.el + ' .field[data-name="dashboardTabList"]',
                defs: {
                    name: 'dashboardTabList',
                    params: {
                        required: this.options.tabListIsNotRequired ? false : true,
                        noEmptyString: true
                    }
                },
                mode: 'edit',
                model: model
            });

            this.headerHtml = this.translate('Edit Dashboard');

            this.dashboardLayout = this.options.dashboardLayout;
        },

        actionSave: function () {
            var dashboardTabListView = this.getView('dashboardTabList');
            dashboardTabListView.fetchToModel();
            if (dashboardTabListView.validate()) {
                return;
            }

            var attributes = {};
            attributes.dashboardTabList = dashboardTabListView.model.get('dashboardTabList');

            var names = dashboardTabListView.model.get('translatedOptions');

            var renameMap = {};
            for (var name in names) {
                if (name !== names[name]) {
                    renameMap[name] = names[name];
                }
            }

            attributes.renameMap = renameMap;

            this.trigger('after:save', attributes);

            this.dialog.close();
        },
    });
});


