/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('views/user/record/detail', 'views/record/detail', function (Dep) {

    return Dep.extend({

        sideView: 'views/user/record/detail-side',

        bottomView: 'views/user/record/detail-bottom',

        editModeDisabled: true,

        setup: function () {
            Dep.prototype.setup.call(this);

            this.setupNonAdminFieldsAccess();

            if (this.model.id == this.getUser().id || this.getUser().isAdmin()) {
                if (!this.model.get('isPortalUser')) {
                    this.buttonList.push({
                        name: 'access',
                        label: 'Access',
                        style: 'default'
                    });
                }

                if (this.model.id == this.getUser().id) {
                    this.dropdownItemList.push({
                        name: 'changePassword',
                        label: 'Change Password',
                        style: 'default'
                    });
                }
            }

            if (this.model.id == this.getUser().id) {
                this.listenTo(this.model, 'after:save', function () {
                    this.getUser().set(this.model.toJSON());
                }.bind(this));
            }

            this.setupFieldAppearance();
        },

        setupNonAdminFieldsAccess: function () {
            if (this.getUser().isAdmin()) return;

            var nonAdminReadOnlyFieldList = [
                'userName',
                'isActive',
                'isAdmin',
                'isPortalUser',
                'teams',
                'roles',
                'password',
                'portals',
                'portalRoles',
                'contact',
                'accounts'
            ];

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

            var isAdminView = this.getFieldView('isAdmin');
            if (isAdminView) {
                this.listenTo(isAdminView, 'change', function () {
                    if (this.model.get('isAdmin')) {
                        this.model.set('isPortalUser', false, {silent: true});
                    }
                }, this);
            }
        },

        controlFieldAppearance: function () {
            if (this.model.get('isAdmin')) {
                this.hideField('isPortalUser');
            } else {
                this.showField('isPortalUser');
            }

            if (this.model.get('isPortalUser')) {
                this.hideField('isAdmin');
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
                this.showField('isAdmin');
                this.showField('roles');
                this.showField('teams');
                this.showField('defaultTeam');
                this.hideField('portals');
                this.hideField('portalRoles');
                this.hideField('contact');
                this.hideField('accounts');
                this.hidePanel('portal');
                this.showField('title');
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
                            [{"name":"isActive"}, {"name":"isAdmin"}],
                            [{"name":"teams"}, {"name":"isPortalUser"}],
                            [{"name":"roles"}, {"name":"defaultTeam"}]
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
                }

                var gridLayout = {
                    type: 'record',
                    layout: this.convertDetailLayout(layout),
                };

                callback(gridLayout);
            }.bind(this));
        }
    });

});
