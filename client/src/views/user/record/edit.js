/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/user/record/edit', ['views/record/edit', 'views/user/record/detail'], function (Dep, Detail) {

    return Dep.extend({

        sideView: 'views/user/record/edit-side',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.setupNonAdminFieldsAccess();

            if (this.model.id === this.getUser().id) {
                this.listenTo(this.model, 'after:save', () => {
                    this.getUser().set(this.model.toJSON());
                });
            }

            this.hideField('sendAccessInfo');

            this.passwordInfoMessage = this.getPasswordSendingMessage();

            if (!this.passwordInfoMessage) {
                this.hideField('passwordInfo');
            }

            let passwordChanged = false;

            this.listenToOnce(this.model, 'change:password', (model) => {
                passwordChanged = true;

                if (this.model.isNew()) {
                    this.controlSendAccessInfoFieldForNew();

                    return;
                }

                this.controlSendAccessInfoField();
            });

            this.listenTo(this.model, 'change', (model) => {
                if (!this.model.isNew() && !passwordChanged) {
                    return;
                }

                if (
                    !model.hasChanged('emailAddress') &&
                    !model.hasChanged('portalsIds')&&
                    !model.hasChanged('password')
                ) {
                    return;
                }

                if (this.model.isNew()) {
                    this.controlSendAccessInfoFieldForNew();

                    return;
                }

                this.controlSendAccessInfoField();
            });

            Detail.prototype.setupFieldAppearance.call(this);

            this.hideField('passwordPreview');

            this.listenTo(this.model, 'change:passwordPreview', (model, value) => {
                value = value || '';

                if (value.length) {
                    this.showField('passwordPreview');
                } else {
                    this.hideField('passwordPreview');
                }
            });


            this.listenTo(this.model, 'after:save', () => {
                this.model.unset('password', {silent: true});
                this.model.unset('passwordConfirm', {silent: true});
            });
        },

        controlSendAccessInfoField: function () {
            if (this.isPasswordSendable() && this.model.get('password')) {
                this.showField('sendAccessInfo');

                return;
            }

            this.hideField('sendAccessInfo');

            this.model.set('sendAccessInfo', false);
        },

        controlSendAccessInfoFieldForNew: function () {
            let skipSettingTrue = this.recordHelper.getFieldStateParam('sendAccessInfo', 'hidden') === false;

            if (this.isPasswordSendable()) {
                this.showField('sendAccessInfo');

                if (!skipSettingTrue) {
                    this.model.set('sendAccessInfo', true);
                }

                return;
            }

            this.hideField('sendAccessInfo');

            this.model.set('sendAccessInfo', false);
        },

        isPasswordSendable: function () {
            if (this.model.isPortal()) {
                if (!(this.model.get('portalsIds') || []).length) {
                    return false;
                }
            }

            if (!this.model.get('emailAddress')) {
                return false;
            }

            return true;
        },


        setupNonAdminFieldsAccess: function () {
            Detail.prototype.setupNonAdminFieldsAccess.call(this);
        },

        controlFieldAppearance: function () {
            Detail.prototype.controlFieldAppearance.call(this);
        },

        getGridLayout: function (callback) {
            this._helper.layoutManager.get(this.model.name, this.options.layoutName || this.layoutName, function (simpleLayout) {
                var layout = Espo.Utils.cloneDeep(simpleLayout);

                layout.push({
                    "label": "Teams and Access Control",
                    "name": "accessControl",
                    "rows": [
                        [{"name":"type"}, {"name":"isActive"}],
                        [{"name":"teams"}, {"name":"defaultTeam"}],
                        [{"name":"roles"}, false]
                    ]
                });
                layout.push({
                    "label": "Portal",
                    "name": "portal",
                    "rows": [
                        [{"name":"portals"}, {"name":"contact"}],
                        [{"name":"portalRoles"}, {"name":"accounts"}]
                    ]
                });
                if (this.getUser().isAdmin() && this.model.isPortal()) {
                    layout.push({
                        "label": "Misc",
                        "name": "portalMisc",
                        "rows": [
                            [{"name":"dashboardTemplate"}, false]
                        ]
                    });
                }

                if (this.type == 'edit' && this.getUser().isAdmin() && !this.model.isApi()) {
                    layout.push({
                        label: 'Password',
                        rows: [
                            [
                                {
                                    name: 'password',
                                    type: 'password',
                                    params: {
                                        required: false,
                                        readyToChange: true,
                                    },
                                    view: 'views/user/fields/password',
                                },
                                {
                                    name: 'generatePassword',
                                    view: 'views/user/fields/generate-password',
                                    customLabel: ''
                                }
                            ],
                            [
                                {
                                    name: 'passwordConfirm',
                                    type: 'password',
                                    params: {
                                        required: false,
                                        readyToChange: true
                                    }
                                },
                                {
                                    name: 'passwordPreview',
                                    view: 'views/fields/base',
                                    params: {
                                        readOnly: true
                                    }
                                }
                            ],
                            [
                                {
                                    name: 'sendAccessInfo'
                                },
                                {
                                    name: 'passwordInfo',
                                    type: 'text',
                                    customLabel: '',
                                    customCode: this.passwordInfoMessage
                                }

                            ]
                        ]
                    });
                }

                if (this.getUser().isAdmin() && this.model.isApi()) {
                    layout.push({
                        "name": "auth",
                        "rows": [
                            [{"name":"authMethod"}, false]
                        ]
                    });
                }

                var gridLayout = {
                    type: 'record',
                    layout: this.convertDetailLayout(layout),
                };

                callback(gridLayout);
            }.bind(this));
        },

        getPasswordSendingMessage: function () {
            if (this.getConfig().get('smtpServer') && this.getConfig().get('smtpServer') !== '') {
                return '';
            }

            var msg = this.translate('setupSmtpBefore', 'messages', 'User').replace('{url}', '#Admin/outboundEmails');

            msg = this.getHelper().transformMarkdownInlineText(msg);

            return msg;
        },

        fetch: function () {
            var data = Dep.prototype.fetch.call(this);

            if (!this.isNew) {
                if ('password' in data) {
                    if (data['password'] == '') {
                        delete data['password'];
                        delete data['passwordConfirm'];
                        this.model.unset('password');
                        this.model.unset('passwordConfirm');
                    }
                }
            }

            return data;
        },

        errorHandlerUserNameExists: function () {
            Espo.Ui.error(this.translate('userNameExists', 'messages', 'User'))
        },

    });
});
