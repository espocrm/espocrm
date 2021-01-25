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

Espo.define('views/email-filter/record/detail', 'views/record/detail', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);
            this.setupFilterFields();
        },

        setupFilterFields: function () {
            this.controlIsGlobal();
            this.listenTo(this.model, 'change:isGlobal', function (model, value, data) {
                this.controlIsGlobal();
                if (data.ui) {
                    if (model.get('isGlobal')) {
                        this.model.set({
                            parentId: null,
                            parentType: null,
                            parentName: null
                        });
                    } else {
                        this.model.set('parentType', 'User');
                        this.model.set('parentId', this.getUser().id);
                        this.model.set('parentName', this.getUser().get('name'));
                    }
                }
            }, this);

            if (!this.getUser().isAdmin()) {
                this.setFieldReadOnly('parent');
                this.setFieldReadOnly('isGlobal');
            }

            if (this.model.isNew()) {
                if (!this.model.get('parentId')) {
                    this.model.set('parentType', 'User');
                    this.model.set('parentId', this.getUser().id);
                    this.model.set('parentName', this.getUser().get('name'));
                }
                if (!this.getUser().isAdmin()) {
                    this.hideField('isGlobal');
                }

                this.setFieldRequired('parent');
            } else {
                this.setFieldReadOnly('isGlobal');
                this.setFieldReadOnly('parent');
            }


            this.controlEmailFolder();
            this.listenTo(this.model, 'change', function () {
                this.controlEmailFolder();
            }, this);
        },

        controlIsGlobal: function () {
            if (this.model.get('isGlobal')) {
                this.hideField('parent');
            } else {
                this.showField('parent');
            }
        },

        controlEmailFolder: function () {
            if (this.model.get('action') !== 'Move to Folder' || this.model.get('parentType') !== 'User') {
                this.hideField('emailFolder');
            } else {
                this.showField('emailFolder');
            }
        }

    });

});

