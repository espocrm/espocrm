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

define('views/user/record/detail', 'views/record/detail', function (Dep) {

    return Dep.extend({

        sideView: 'views/user/record/detail-side',

        bottomView: 'views/user/record/detail-bottom',

        editModeDisabled: true,

        setup: function () {
            Dep.prototype.setup.call(this);

            this.setupNonAdminFieldsAccess();

            if (this.getUser().isAdmin()) {
                if (!this.model.isPortal()) {
                    this.addButton({
                        name: 'access',
                        label: 'Access',
                        style: 'default'
                    });
                }
            }

            if (
                (this.model.id == this.getUser().id || this.getUser().isAdmin()) &&
                (this.model.isRegular() || this.model.isAdmin()) &&
                this.getConfig().get('auth2FA')
            ) {
                this.addButton({
                    name: 'viewSecurity',
                    label: 'Security',
                });
            }

            if (
                this.model.id == this.getUser().id
                &&
                !this.model.isApi()
                &&
                (this.getUser().isAdmin() || !this.getHelper().getAppParam('passwordChangeForNonAdminDisabled'))
            ) {
                this.addDropdownItem({
                    name: 'changePassword',
                    label: 'Change Password',
                    style: 'default'
                });
            }

            if (
                this.getUser().isAdmin()
                &&
                (this.model.isRegular() || this.model.isAdmin() || this.model.isPortal())
                &&
                !this.model.isSuperAdmin()
            ) {
                this.addDropdownItem({
                    name: 'generateNewPassword',
                    label: 'Generate New Password',
                    action: 'generateNewPassword',
                    hidden: !this.model.get('emailAddress'),
                });

                if (!this.model.get('emailAddress')) {
                    this.listenTo(this.model, 'sync', function () {
                        if (this.model.get('emailAddress')) {
                            this.showActionItem('generateNewPassword');
                        } else {
                            this.hideActionItem('generateNewPassword');
                        }
                    }, this);
                }
            }

            if (this.model.isPortal() || this.model.isApi()) {
                this.hideActionItem('duplicate');
            }

            if (this.model.id == this.getUser().id) {
                this.listenTo(this.model, 'after:save', function () {
                    this.getUser().set(this.model.toJSON());
                }.bind(this));
            }

            this.setupFieldAppearance();
        },

        setupActionItems: function () {
            Dep.prototype.setupActionItems.call(this);

            if (this.model.isApi() && this.getUser().isAdmin()) {
                this.addDropdownItem({
                    'label': 'Generate New API Key',
                    'name': 'generateNewApiKey'
                });
            }
        },

        setupNonAdminFieldsAccess: function () {
            if (this.getUser().isAdmin()) {
                return;
            }

            var nonAdminReadOnlyFieldList = [
                'userName',
                'isActive',
                'teams',
                'roles',
                'password',
                'portals',
                'portalRoles',
                'contact',
                'accounts',
                'type',
                'emailAddress',
            ];

            nonAdminReadOnlyFieldList = nonAdminReadOnlyFieldList.filter(
                function (item) {
                    if (!this.model.hasField(item)) {
                        return true;
                    }

                    var aclDefs = this.getMetadata().get(['entityAcl', 'User', 'fields', item]);

                    if (!aclDefs) {
                        return true;
                    }

                    if (aclDefs.nonAdminReadOnly) {
                        return true;
                    }

                    return false;
                }.bind(this),
            );

            nonAdminReadOnlyFieldList.forEach(function (field) {
                this.setFieldReadOnly(field, true);
            }, this);

            if (!this.getAcl().checkScope('Team')) {
                this.setFieldReadOnly('defaultTeam', true);
            }
        },

        setupFieldAppearance: function () {

            this.controlFieldAppearance();
            this.listenTo(this.model, 'change', function () {
                this.controlFieldAppearance();
            }, this);
        },

        controlFieldAppearance: function () {
            if (this.model.get('type') === 'portal') {
                this.hideField('roles');
                this.hideField('teams');
                this.hideField('defaultTeam');
                this.showField('portals');
                this.showField('portalRoles');
                this.showField('contact');
                this.showField('accounts');
                this.showPanel('portal');
                this.hideField('title');
            } else {
                this.showField('roles');
                this.showField('teams');
                this.showField('defaultTeam');
                this.hideField('portals');
                this.hideField('portalRoles');
                this.hideField('contact');
                this.hideField('accounts');
                this.hidePanel('portal');

                if (this.model.get('type') === 'api') {
                    this.hideField('title');
                    this.hideField('emailAddress');
                    this.hideField('phoneNumber');
                    this.hideField('name');
                    this.hideField('gender');

                    if (this.model.get('authMethod') === 'Hmac') {
                        this.showField('secretKey');
                    } else {
                        this.hideField('secretKey');
                    }

                } else {
                    this.showField('title');
                }
            }

            if (this.model.id === this.getUser().id) {
                this.setFieldReadOnly('type');
            } else {
                if (this.model.get('type') == 'admin' || this.model.get('type') == 'regular') {
                    this.setFieldNotReadOnly('type');
                    this.setFieldOptionList('type', ['regular', 'admin']);
                } else {
                    this.setFieldReadOnly('type');
                }
            }

            if (
                !this.getConfig().get('auth2FA')
                ||
                !(this.model.isRegular() || this.model.isAdmin())
            ) {
                this.hideField('auth2FA');
            }
        },

        actionChangePassword: function () {
            this.notify('Loading...');

            this.createView('changePassword', 'views/modals/change-password', {
                userId: this.model.id
            }, function (view) {
                view.render();
                this.notify(false);

                this.listenToOnce(view, 'changed', function () {
                    setTimeout(function () {
                        this.getBaseController().logout();
                    }.bind(this), 2000);
                }, this);

            }.bind(this));
        },

        actionPreferences: function () {
            this.getRouter().navigate('#Preferences/edit/' + this.model.id, {trigger: true});
        },

        actionEmailAccounts: function () {
            this.getRouter().navigate('#EmailAccount/list/userId=' + this.model.id, {trigger: true});
        },

        actionExternalAccounts: function () {
            this.getRouter().navigate('#ExternalAccount', {trigger: true});
        },

        actionAccess: function () {
            this.notify('Loading...');

            $.ajax({
                url: 'User/action/acl',
                type: 'GET',
                data: {
                    id: this.model.id,
                }
            }).done(function (aclData) {
                this.createView('access', 'views/user/modals/access', {
                    aclData: aclData,
                    model: this.model,
                }, function (view) {
                    this.notify(false);
                    view.render();
                }.bind(this));
            }.bind(this));
        },

        getGridLayout: function (callback) {
            this._helper.layoutManager.get(this.model.name, this.options.layoutName || this.layoutName, function (simpleLayout) {
                var layout = Espo.Utils.cloneDeep(simpleLayout);

                if (!this.getUser().isPortal()) {
                    layout.push({
                        "label": "Teams and Access Control",
                        "name": "accessControl",
                        "rows": [
                            [{"name":"type"}, {"name":"isActive"}],
                            [{"name":"teams"}, {"name":"defaultTeam"}],
                            [{"name":"roles"}, false]
                        ]
                    });

                    if (this.model.isPortal()) {
                        layout.push({
                            "label": "Portal",
                            "name": "portal",
                            "rows": [
                                [{"name":"portals"}, {"name":"contact"}],
                                [{"name":"portalRoles"}, {"name":"accounts"}]
                            ]
                        });
                        if (this.getUser().isAdmin()) {
                            layout.push({
                                "label": "Misc",
                                "name": "portalMisc",
                                "rows": [
                                    [{"name":"dashboardTemplate"}, false]
                                ]
                            });
                        }
                    }
                }

                if (this.getUser().isAdmin() && this.model.isApi()) {
                    layout.push({
                        "name": "auth",
                        "rows": [
                            [{"name":"authMethod"}, false],
                            [{"name":"apiKey"}, {"name":"secretKey"}],
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

        actionGenerateNewApiKey: function () {
            this.confirm(this.translate('confirmation', 'messages'), function () {
                this.ajaxPostRequest('User/action/generateNewApiKey', {
                    id: this.model.id
                }).then(function (data) {
                    this.model.set(data);
                }.bind(this));
            }.bind(this));
        },

        actionViewSecurity: function () {
            this.createView('dialog', 'views/user/modals/security', {
                userModel: this.model,
            }, function (view) {
                view.render();
            }, this);
        },

        actionGenerateNewPassword: function () {
            this.confirm(
                this.translate('generateAndSendNewPassword', 'messages', 'User')
            ).then(
                function () {
                    Espo.Ui.notify(this.translate('pleaseWait', 'messages'));
                    Espo.Ajax.postRequest('User/action/generateNewPassword', {
                        id: this.model.id
                    }).then(
                        function () {
                            Espo.Ui.success(this.translate('Done'));
                        }.bind(this)
                    );
                }.bind(this)
            );
        },

    });
});
