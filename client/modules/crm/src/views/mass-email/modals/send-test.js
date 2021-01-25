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

Espo.define('crm:views/mass-email/modals/send-test', ['views/modal', 'model'], function (Dep, Model) {

    return Dep.extend({

        scope: 'MassEmail',

        template: 'crm:mass-email/modals/send-test',

        setup: function () {
            Dep.prototype.setup.call(this);
            this.headerHtml = this.translate('Send Test', 'labels', 'MassEmail');

            var model = new Model();

            model.set('usersIds', [this.getUser().id]);
            var usersNames = {};
            usersNames[this.getUser().id] = this.getUser().get('name');
            model.set('usersNames', usersNames);

            this.createView('users', 'views/fields/link-multiple', {
                model: model,
                el: this.options.el + ' .field[data-name="users"]',
                foreignScope: 'User',
                defs: {
                    name: 'users',
                    params: {
                    }
                },
                mode: 'edit'
            });

            this.createView('contacts', 'views/fields/link-multiple', {
                model: model,
                el: this.options.el + ' .field[data-name="contacts"]',
                foreignScope: 'Contact',
                defs: {
                    name: 'contacts',
                    params: {
                    }
                },
                mode: 'edit'
            });

            this.createView('leads', 'views/fields/link-multiple', {
                model: model,
                el: this.options.el + ' .field[data-name="leads"]',
                foreignScope: 'Lead',
                defs: {
                    name: 'leads',
                    params: {
                    }
                },
                mode: 'edit'
            });

            this.createView('accounts', 'views/fields/link-multiple', {
                model: model,
                el: this.options.el + ' .field[data-name="accounts"]',
                foreignScope: 'Account',
                defs: {
                    name: 'accounts',
                    params: {
                    }
                },
                mode: 'edit'
            });

            this.buttonList.push({
                name: 'sendTest',
                label: 'Send Test',
                style: 'danger'
            });

            this.buttonList.push({
                name: 'cancel',
                label: 'Cancel'
            });
        },

        actionSendTest: function () {

            var list = [];

            this.getView('users').fetch().usersIds.forEach(function (id) {
                list.push({
                    id: id,
                    type: 'User'
                });
            });
            this.getView('contacts').fetch().contactsIds.forEach(function (id) {
                list.push({
                    id: id,
                    type: 'Contact'
                });
            });
            this.getView('leads').fetch().leadsIds.forEach(function (id) {
                list.push({
                    id: id,
                    type: 'Lead'
                });
            });
            this.getView('accounts').fetch().accountsIds.forEach(function (id) {
                list.push({
                    id: id,
                    type: 'Account'
                });
            });


            if (list.length == 0) {
                alert(this.translate('selectAtLeastOneTarget', 'messages', 'MassEmail'));
                return;
            }

            this.disableButton('sendTest');

            $.ajax({
                url: 'MassEmail/action/sendTest',
                type: 'POST',
                data: JSON.stringify({
                    id: this.model.id,
                    targetList: list
                }),
                error: function () {
                    this.enableButton('sendTest');
                }.bind(this)
            }).done(function () {
                Espo.Ui.success(this.translate('testSent', 'messages', 'MassEmail'));
                this.close();
            }.bind(this));
        }

    });
});

