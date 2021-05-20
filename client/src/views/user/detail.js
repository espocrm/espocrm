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

define('views/user/detail', 'views/detail', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.getUser().isPortal()) {
                this.rootLinkDisabled = true;
            }

            if (this.model.id === this.getUser().id || this.getUser().isAdmin()) {

                if (this.model.isRegular() || this.model.isAdmin() || this.model.isPortal()) {
                    this.addMenuItem('dropdown', {
                        name: 'preferences',
                        label: 'Preferences',
                        style: 'default',
                        action: "preferences",
                        link: '#Preferences/edit/' + this.getUser().id
                    });
                }

                if (this.model.isRegular() || this.model.isAdmin()) {
                    if (
                        (this.getAcl().check('EmailAccountScope') && this.model.id === this.getUser().id) ||
                        this.getUser().isAdmin()
                    ) {
                        this.addMenuItem('dropdown', {
                            name: 'emailAccounts',
                            label: "Email Accounts",
                            style: 'default',
                            action: "emailAccounts",
                            link: '#EmailAccount/list/userId=' +
                                this.model.id + '&userName=' + encodeURIComponent(this.model.get('name'))
                        });
                    }

                    if (this.model.id === this.getUser().id && this.getAcl().checkScope('ExternalAccount')) {
                        this.menu.buttons.push({
                            name: 'externalAccounts',
                            label: 'External Accounts',
                            style: 'default',
                            action: "externalAccounts",
                            link: '#ExternalAccount'
                        });
                    }
                }
            }

            if (this.getAcl().checkScope('Calendar') && (this.model.isRegular() || this.model.isAdmin())) {
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
                    html: '<span class="far fa-calendar-alt"></span> ' + this.translate('Calendar', 'scopeNames'),
                    style: 'default',
                    link: '#Calendar/show/userId=' +
                        this.model.id + '&userName=' + encodeURIComponent(this.model.get('name')),
                    hidden: !showActivities
                });
            }
        },

        actionPreferences: function () {
            this.getRouter().navigate('#Preferences/edit/' + this.model.id, {trigger: true});
        },

        actionEmailAccounts: function () {
            this.getRouter()
                .navigate(
                    '#EmailAccount/list/userId=' + this.model.id +
                    '&userName=' + encodeURIComponent(this.model.get('name')),
                    {trigger: true}
                );
        },

        actionExternalAccounts: function () {
            this.getRouter().navigate('#ExternalAccount', {trigger: true});
        },

    });
});
