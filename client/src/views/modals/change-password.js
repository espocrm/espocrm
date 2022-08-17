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

define('views/modals/change-password', ['views/modal'], function (Dep) {

    return Dep.extend({

        cssName: 'change-password',

        template: 'modals/change-password',

        className: 'dialog dialog-record',

        setup: function () {
            this.buttonList = [
                {
                    name: 'change',
                    label: 'Change',
                    style: 'danger',
                },
                {
                    name: 'cancel',
                    label: 'Cancel',
                },
            ];

            this.headerText = this.translate('Change Password', 'labels', 'User');

            this.wait(true);

            this.getModelFactory().create('User', user => {
                this.model = user;

                this.createView('currentPassword', 'views/fields/password', {
                    model: user,
                    mode: 'edit',
                    el: this.options.el + ' .field[data-name="currentPassword"]',
                    defs: {
                        name: 'currentPassword',
                        params: {
                            required: true,
                        }
                    }
                });

                this.createView('password', 'views/user/fields/password', {
                    model: user,
                    mode: 'edit',
                    el: this.options.el + ' .field[data-name="password"]',
                    defs: {
                        name: 'password',
                        params: {
                            required: true,
                        }
                    }
                });

                this.createView('passwordConfirm', 'views/fields/password', {
                    model: user,
                    mode: 'edit',
                    el: this.options.el + ' .field[data-name="passwordConfirm"]',
                    defs: {
                        name: 'passwordConfirm',
                        params: {
                            required: true,
                        }
                    }
                });

                this.wait(false);
            });
        },


        actionChange: function () {
            this.getView('currentPassword').fetchToModel();
            this.getView('password').fetchToModel();
            this.getView('passwordConfirm').fetchToModel();

            var notValid =
                this.getView('currentPassword').validate() ||
                this.getView('password').validate() ||
                this.getView('passwordConfirm').validate();

            if (notValid) {
                return;
            }

            this.$el.find('button[data-name="change"]').addClass('disabled');

            Espo.Ajax
                .postRequest('User/action/changeOwnPassword', {
                    currentPassword: this.model.get('currentPassword'),
                    password: this.model.get('password'),
                })
                .then(() => {
                    Espo.Ui.success(this.translate('passwordChanged', 'messages', 'User'));

                    this.trigger('changed');
                    this.close();
                })
                .catch(() => {
                    this.$el.find('button[data-name="change"]').removeClass('disabled');
                });
        },
    });
});
