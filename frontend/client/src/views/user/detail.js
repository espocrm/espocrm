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

Espo.define('views/user/detail', 'views/detail', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);
            if (this.model.id == this.getUser().id || this.getUser().isAdmin()) {
                this.menu.buttons.push({
                    name: 'preferences',
                    label: 'Preferences',
                    style: 'default',
                    action: "preferences"
                });


                if ((this.getAcl().check('EmailAccountScope') && this.model.id == this.getUser().id) || this.getUser().isAdmin()) {
                    this.menu.buttons.push({
                        name: 'emailAccounts',
                        label: "Email Accounts",
                        style: 'default',
                        action: "emailAccounts"
                    });
                }

                if (this.model.id == this.getUser().id) {
                    this.menu.buttons.push({
                        name: 'externalAccounts',
                        label: 'External Accounts',
                        style: 'default',
                        action: "externalAccounts"
                    });
                }
            }

            var showActivities = this.getAcl().checkUserPermission(this.model);
            if (!showActivities) {
                if (this.getAcl().get('userPermission') === 'team') {
                    if (!this.model.has('teamsIds')) {
                        this.listenToOnce(this.model, 'sync', function () {
                            if (this.getAcl().checkUserPermission(this.model)) {
                                this.showHeaderActionItem('calendar');
                            }
                        }, this);
                    }
                }
            }

            this.menu.buttons.push({
                name: 'calendar',
                html: this.translate('Calendar', 'scopeNames'),
                style: 'default',
                link: '#Calendar/show/userId=' + this.model.id + '&userName=' + this.model.get('name'),
                hidden: !showActivities
            });
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
    });
});

