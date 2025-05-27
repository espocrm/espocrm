/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

import DetailRecordView from 'views/record/detail';

class UserDetailRecordView extends DetailRecordView {

    sideView = 'views/user/record/detail-side'
    bottomView = 'views/user/record/detail-bottom'

    editModeDisabled = true

    /**
     * @name model
     * @type module:models/user
     * @memberOf UserDetailRecordView#
     */

    setup() {
        super.setup();

        this.setupNonAdminFieldsAccess();

        if (this.getUser().isAdmin() && !this.model.isPortal()) {
            this.addButton({
                name: 'access',
                label: 'Access',
                style: 'default',
                onClick: () => this.actionAccess(),
            });
        }

        const isPortalUser = this.model.isPortal() ||
            this.model.id === this.getUser().id && this.getUser().isPortal();

        if (
            (this.model.id === this.getUser().id || this.getUser().isAdmin()) &&
            this.getConfig().get('auth2FA') &&
            (
                (this.model.isRegular() || this.model.isAdmin()) ||
                isPortalUser && this.getConfig().get('auth2FAInPortal')
            )
        ) {
            this.addButton({
                name: 'viewSecurity',
                label: 'Security',
            });
        }

        if (
            this.model.id === this.getUser().id &&
            !this.model.isApi() &&
            (this.getUser().isAdmin() || !this.getHelper().getAppParam('passwordChangeForNonAdminDisabled'))
        ) {
            this.addDropdownItem({
                name: 'changePassword',
                label: 'Change Password',
                style: 'default'
            });
        }

        if (
            this.getUser().isAdmin() &&
            (
                this.model.isRegular() ||
                this.model.isAdmin() ||
                this.model.isPortal()
            ) &&
            !this.model.isSuperAdmin()
        ) {
            this.addDropdownItem({
                name: 'sendPasswordChangeLink',
                label: 'Send Password Change Link',
                action: 'sendPasswordChangeLink',
                hidden: !this.model.get('emailAddress'),
            });

            this.addDropdownItem({
                name: 'generateNewPassword',
                label: 'Generate New Password',
                action: 'generateNewPassword',
                hidden: !this.model.get('emailAddress'),
            });

            if (!this.model.get('emailAddress')) {
                this.listenTo(this.model, 'sync', () => {
                    if (this.model.get('emailAddress')) {
                        this.showActionItem('generateNewPassword');
                        this.showActionItem('sendPasswordChangeLink');
                    } else {
                        this.hideActionItem('generateNewPassword');
                        this.hideActionItem('sendPasswordChangeLink');
                    }
                });
            }
        }

        if (this.model.isPortal() || this.model.isApi()) {
            this.hideActionItem('duplicate');
        }

        if (this.model.id === this.getUser().id) {
            this.listenTo(this.model, 'after:save', () => {
                this.getUser().set(this.model.getClonedAttributes());
            });
        }

        if (
            this.getUser().isAdmin() &&
            this.model.isRegular() &&
            !this.getConfig().get('authAnotherUserDisabled')
        ) {
            this.addDropdownItem({
                label: 'Log in',
                name: 'login',
                action: 'login',
            });
        }

        this.setupFieldAppearance();
    }

    setupActionItems() {
        super.setupActionItems();

        if (this.model.isApi() && this.getUser().isAdmin()) {
            this.addDropdownItem({
                'label': 'Generate New API Key',
                'name': 'generateNewApiKey'
            });
        }
    }

    setupNonAdminFieldsAccess() {
        if (this.getUser().isAdmin()) {
            return;
        }

        let nonAdminReadOnlyFieldList = [
            'userName',
            'isActive',
            'teams',
            'roles',
            'password',
            'portals',
            'portalRoles',
            'defaultTeam',
            'contact',
            'accounts',
            'type',
            'emailAddress',
        ];

        nonAdminReadOnlyFieldList = nonAdminReadOnlyFieldList.filter(item => {
            if (!this.model.hasField(item)) {
                return true;
            }

            const aclDefs = /** @type {Object.<string, *>|null} */
                this.getMetadata().get(['entityAcl', 'User', 'fields', item]);

            if (!aclDefs) {
                return true;
            }

            if (aclDefs.nonAdminReadOnly) {
                return true;
            }

            return false;
        });

        nonAdminReadOnlyFieldList.forEach((field) => {
            this.setFieldReadOnly(field, true);
        });

        if (!this.getAcl().checkScope('Team')) {
            this.setFieldReadOnly('defaultTeam', true);
        }

        this.hideField('layoutSet', true);
    }

    setupFieldAppearance() {
        this.controlFieldAppearance();

        this.listenTo(this.model, 'change', () => {
            this.controlFieldAppearance();
        });
    }

    controlFieldAppearance() {
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
            if (this.model.get('type') === 'admin' || this.model.get('type') === 'regular') {
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
    }

    // noinspection JSUnusedGlobalSymbols
    actionChangePassword() {
        Espo.Ui.notifyWait();

        this.createView('changePassword', 'views/modals/change-password', {userId: this.model.id}, view => {
            view.render();
            Espo.Ui.notify(false);

            this.listenToOnce(view, 'changed', () => {
                setTimeout(() => {
                    this.getBaseController().logout();
                }, 2000);
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionPreferences() {
        this.getRouter().navigate('#Preferences/edit/' + this.model.id, {trigger: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionEmailAccounts() {
        this.getRouter().navigate('#EmailAccount/list/userId=' + this.model.id, {trigger: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionExternalAccounts() {
        this.getRouter().navigate('#ExternalAccount', {trigger: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionAccess() {
        Espo.Ui.notifyWait();

        Espo.Ajax.getRequest(`User/${this.model.id}/acl`).then(aclData => {
            this.createView('access', 'views/user/modals/access', {
                aclData: aclData,
                model: this.model,
            }, view => {
                Espo.Ui.notify(false);

                view.render();
            });
        });
    }

    getGridLayout(callback) {
        const layoutName = this.options.layoutName || this.layoutName;

        this.getHelper().layoutManager.get(this.model.entityType, layoutName, simpleLayout => {
            const layout = Espo.Utils.cloneDeep(simpleLayout);

            const hasTab = layout.find(it => it.tabBreak) !== undefined;

            if (!this.getUser().isPortal()) {
                layout.push({
                    label: "Teams and Access Control",
                    name: "accessControl",
                    tabBreak: hasTab,
                    tabLabel: this.translate('Settings', 'scopeNames'),
                    rows: [
                        [{"name":"type"}, {"name":"isActive"}],
                        [{"name":"teams"}, {"name":"defaultTeam"}],
                        [{"name":"roles"}, false],
                    ]
                });

                if (this.model.isPortal()) {
                    layout.push({
                        "label": "Portal",
                        "name": "portal",
                        "rows": [
                            [{"name": "portals"}, {"name": "accounts"}],
                            [{"name": "portalRoles"}, {"name": "contact"}],
                        ]
                    });

                    if (this.getUser().isAdmin()) {
                        layout.push({
                            "label": "Misc",
                            "name": "portalMisc",
                            "rows": [
                                [{"name":"dashboardTemplate"}, false],
                            ],
                        });
                    }
                }

                if (this.model.isAdmin() || this.model.isRegular()) {
                    layout.push({
                        "label": "Misc",
                        "name": "misc",
                        "rows": [
                            [{"name": "workingTimeCalendar"}, {"name": "layoutSet"}],
                        ]
                    });
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

            this.detailLayout = layout;

            const gridLayout = {
                type: 'record',
                layout: this.convertDetailLayout(layout),
            };

            callback(gridLayout);
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionGenerateNewApiKey() {
        this.confirm(this.translate('confirmation', 'messages'), () => {
            Espo.Ajax
                .postRequest('UserSecurity/apiKey/generate', {id: this.model.id})
                .then((data) => {
                    this.model.set(data);
                });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionViewSecurity() {
        this.createView('dialog', 'views/user/modals/security', {
            userModel: this.model,
        }, view => {
            view.render();
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionSendPasswordChangeLink() {
        this.confirm({
            message: this.translate('sendPasswordChangeLinkConfirmation', 'messages', 'User'),
            confirmText: this.translate('Send', 'labels', 'Email'),
        })
        .then(() => {
            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

            Espo.Ajax
                .postRequest('UserSecurity/password/recovery', {id: this.model.id})
                .then(() => {
                    Espo.Ui.success(this.translate('Done'));
                });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionGenerateNewPassword() {
        this.confirm(
            this.translate('generateAndSendNewPassword', 'messages', 'User')
        ).then(() => {
            Espo.Ui.notifyWait();

            Espo.Ajax
                .postRequest('UserSecurity/password/generate', {id: this.model.id})
                .then(() => {
                    Espo.Ui.success(this.translate('Done'));
                });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionLogin() {
        const anotherUser = this.model.get('userName');
        const username = this.getUser().get('userName');

        this.createView('dialog', 'views/user/modals/login-as', {
                model: this.model,
                anotherUser: anotherUser,
                username: username,
            })
            .then(view => view.render());
    }
}

export default UserDetailRecordView;
