/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

define('views/admin/authentication', ['views/settings/record/edit'], function (Dep) {

    return Dep.extend({

        layoutName: 'authentication',

        saveAndContinueEditingAction: false,

        setup: function () {
            this.methodList = [];

            let defs = this.getMetadata().get(['authenticationMethods']) || {};

            for (let method in defs) {
                if (defs[method].settings && defs[method].settings.isAvailable) {
                    this.methodList.push(method);
                }
            }

            this.authFields = {};

            Dep.prototype.setup.call(this);

            this.handlePanelsVisibility();

            this.listenTo(this.model, 'change:authenticationMethod', () => {
                this.handlePanelsVisibility();
            });

            this.manage2FAFields();

            this.listenTo(this.model, 'change:auth2FA', () => {
                this.manage2FAFields();
            });

            this.managePasswordRecoveryFields();

            this.listenTo(this.model, 'change:passwordRecoveryDisabled', () => {
                this.managePasswordRecoveryFields();
            });
        },

        setupBeforeFinal: function () {
            this.dynamicLogicDefs = {
                fields: {},
                panels: {},
            };

            this.methodList.forEach(method => {
                let fieldList = this.getMetadata().get(['authenticationMethods', method, 'settings', 'fieldList']);

                if (fieldList) {
                    this.authFields[method] = fieldList;
                }

                let mDynamicLogicFieldsDefs = this.getMetadata()
                    .get(['authenticationMethods', method, 'settings', 'dynamicLogic', 'fields']);

                if (mDynamicLogicFieldsDefs) {
                    for (let f in mDynamicLogicFieldsDefs) {
                        this.dynamicLogicDefs.fields[f] = Espo.Utils.cloneDeep(mDynamicLogicFieldsDefs[f]);
                    }
                }
            });

            Dep.prototype.setupBeforeFinal.call(this);
        },

        modifyDetailLayout: function (layout) {
            this.methodList.forEach(method => {
                let mLayout = this.getMetadata().get(['authenticationMethods', method, 'settings', 'layout']);

                if (!mLayout) {
                    return;
                }

                mLayout = Espo.Utils.cloneDeep(mLayout);
                mLayout.name = method;

                this.prepareLayout(mLayout, method);

                layout.push(mLayout);
            });
        },

        prepareLayout: function (layout, method) {
            layout.rows.forEach(row => {
                row
                    .filter(item => !item.noLabel && !item.labelText && item.name)
                    .forEach(item => {
                        let labelText = this.translate(item.name, 'fields', 'Settings');

                        if (labelText && labelText.toLowerCase().indexOf(method.toLowerCase() + ' ') === 0) {
                            item.labelText = labelText.substring(method.length + 1);
                        }
                    });
            });
        },

        handlePanelsVisibility: function () {
            var authenticationMethod = this.model.get('authenticationMethod');

            this.methodList.forEach(method => {
                var fieldList = (this.authFields[method] || []);

                if (method !== authenticationMethod) {
                    this.hidePanel(method);

                    fieldList.forEach(field => {
                        this.hideField(field);
                    });

                    return;
                }

                this.showPanel(method);

                fieldList.forEach(field => {
                    this.showField(field);
                });

                this.processDynamicLogic();
            });
        },

        manage2FAFields: function () {
            if (this.model.get('auth2FA')) {
                this.showField('auth2FAForced');
                this.showField('auth2FAMethodList');
                this.showField('auth2FAInPortal');
                this.setFieldRequired('auth2FAMethodList');

                return;
            }

            this.hideField('auth2FAForced');
            this.hideField('auth2FAMethodList');
            this.hideField('auth2FAInPortal');
            this.setFieldNotRequired('auth2FAMethodList');
        },

        managePasswordRecoveryFields: function () {
            if (!this.model.get('passwordRecoveryDisabled')) {
                this.showField('passwordRecoveryForAdminDisabled');
                this.showField('passwordRecoveryForInternalUsersDisabled');
                this.showField('passwordRecoveryNoExposure');

                return;
            }

            this.hideField('passwordRecoveryForAdminDisabled');
            this.hideField('passwordRecoveryForInternalUsersDisabled');
            this.hideField('passwordRecoveryNoExposure');
        },
    });
});
