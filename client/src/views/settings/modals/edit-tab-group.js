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

define('views/settings/modals/edit-tab-group', ['views/modal', 'model'], function (Dep, Model) {

    return Dep.extend({

        templateContent: '<div class="record">{{{record}}}</div>',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.headerHtml = this.translate('Group Tab', 'labels', 'Settings');

            this.buttonList.push({
                name: 'apply',
                label: 'Apply',
                style: 'danger',
            });

            this.buttonList.push({
                name: 'cancel',
                label: 'Cancel',
            });

            var detailLayout = [
                {
                    rows: [
                        [
                            {
                                name: 'text',
                                labelText: this.translate('label', 'fields', 'Admin'),
                            },
                            {
                                name: 'iconClass',
                                labelText: this.translate('iconClass', 'fields', 'EntityManager'),
                            },
                            {
                                name: 'color',
                                labelText: this.translate('color', 'fields', 'EntityManager'),
                            },
                        ],
                        [
                            {
                                name: 'itemList',
                                labelText: this.translate('tabList', 'fields', 'Settings'),
                            },
                            false
                        ]
                    ]
                }
            ];

            var model = this.model = new Model();

            model.name = 'GroupTab';

            model.set(this.options.itemData);

            model.setDefs({
                fields: {
                    text: {
                        type: 'varchar',
                    },
                    iconClass: {
                        type: 'base',
                        view: 'views/admin/entity-manager/fields/icon-class',
                    },
                    color: {
                        type: 'base',
                        view: 'views/fields/colorpicker',
                    },
                    itemList: {
                        type: 'array',
                        view: 'views/settings/fields/group-tab-list',
                    },
                },
            });

            this.createView('record', 'views/record/edit-for-modal', {
                detailLayout: detailLayout,
                model: model,
                el: this.getSelector() + ' .record',
            });
        },

        actionApply: function () {
            var recordView = this.getView('record');

            if (recordView.validate()) {
                return;
            }

            var data = recordView.fetch();

            this.trigger('apply', data);
        },

    });
});
