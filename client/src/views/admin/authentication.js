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

define('views/admin/authentication', 'views/settings/record/edit', function (Dep) {

    return Dep.extend({

        layoutName: 'authentication',

        saveAndContinueEditingAction: false,

        setup: function () {
            this.methodList = [];

            var defs = this.getMetadata().get(['authenticationMethods']) || {};
            for (var method in defs) {
                if (defs[method].settings && defs[method].settings.isAvailable) {
                    this.methodList.push(method);
                }
            }

            this.authFields = {};

            Dep.prototype.setup.call(this);

            this.handlePanelsVisibility();
            this.listenTo(this.model, 'change:authenticationMethod', function () {
                this.handlePanelsVisibility();
            }, this);

            this.manage2FAFields();
            this.listenTo(this.model, 'change:auth2FA', function () {
                this.manage2FAFields();
            }, this);

            this.managePasswordRecoveryFields();
            this.listenTo(this.model, 'change:passwordRecoveryDisabled', function () {
                this.managePasswordRecoveryFields();
            }, this);
        },

        setupBeforeFinal: function () {
            this.dynamicLogicDefs = {
                fields: {},
                panels: {},
            };

            this.methodList.forEach(function (method) {
                var fieldList = this.getMetadata().get(['authenticationMethods', method, 'settings', 'fieldList']);
                if (fieldList) {
                    this.authFields[method] = fieldList;
                }
                var mDynamicLogicFieldsDefs = this.getMetadata().get(['authenticationMethods', method, 'settings', 'dynamicLogic', 'fields']);
                if (mDynamicLogicFieldsDefs) {
                    for (var f in mDynamicLogicFieldsDefs) {
                        this.dynamicLogicDefs.fields[f] = Espo.Utils.cloneDeep(mDynamicLogicFieldsDefs[f]);
                    }
                }
            }, this);

            Dep.prototype.setupBeforeFinal.call(this);
        },

        modifyDetailLayout: function (layout) {
            this.methodList.forEach(function (method) {
                var mLayout = this.getMetadata().get(['authenticationMethods', method, 'settings', 'layout']);
                if (mLayout) {
                    mLayout = Espo.Utils.cloneDeep(mLayout);
                    mLayout.name = method;
                    layout.push(mLayout);
                }
            }, this);
        },

        handlePanelsVisibility: function () {
            var authenticationMethod = this.model.get('authenticationMethod');

            this.methodList.forEach(function (method) {
                var fieldList = (this.authFields[method] || []);

                if (method != authenticationMethod) {
                    this.hidePanel(method);

                    fieldList.forEach(function (field) {
                        this.hideField(field);
                    }, this);
                } else {
                    this.showPanel(method);

                    fieldList.forEach(function (field) {
                        this.showField(field);
                    }, this);

                    this.processDynamicLogic();
                }
            }, this);
        },

        manage2FAFields: function () {
            if (this.model.get('auth2FA')) {
                this.showField('auth2FAForced');
                this.showField('auth2FAMethodList');
                this.setFieldRequired('auth2FAMethodList');
            } else {
                this.hideField('auth2FAForced');
                this.hideField('auth2FAMethodList');
                this.setFieldNotRequired('auth2FAMethodList');
            }
        },

        managePasswordRecoveryFields: function () {
            if (!this.model.get('passwordRecoveryDisabled')) {
                this.showField('passwordRecoveryForAdminDisabled');
                this.showField('passwordRecoveryForInternalUsersDisabled');
                this.showField('passwordRecoveryNoExposure');
            } else {
                this.hideField('passwordRecoveryForAdminDisabled');
                this.hideField('passwordRecoveryForInternalUsersDisabled');
                this.hideField('passwordRecoveryNoExposure');
            }
        },

    });
});
