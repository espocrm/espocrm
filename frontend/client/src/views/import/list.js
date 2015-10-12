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

Espo.define('views/import/list', 'views/list', function (Dep) {

    return Dep.extend({

        createButton: false,

        setup: function () {
            Dep.prototype.setup.call(this);

            this.menu.buttons.unshift({
                label: this.translate('Import', 'scopeNames'),
                link: '#Import',
                style: 'danger',
                acl: 'edit'
            });
        },

        actionCreateEmailAccount: function () {
            if (this.options.userId) {
                this.getRouter().dispatch('EmailAccount', 'create', {
                    attributes: {
                        assignedUserId: this.options.userId,
                        assignedUserName: this.options.userId
                    }
                });
                this.getRouter().navigate('#EmailAccount/create', {trigger: false});
            } else {
                this.getRouter().navigate('#EmailAccount/create', {trigger: true});
            }
        },


    });
});

