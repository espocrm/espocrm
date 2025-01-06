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

import ModalView from 'views/modal';

class ChangePasswordModalView extends ModalView {

    template = 'modals/change-password'

    cssName = 'change-password'
    className = 'dialog dialog-record'

    setup() {
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

        const promise = this.getModelFactory().create('User', user => {
            this.model = user;

            this.createView('currentPassword', 'views/fields/password', {
                model: user,
                mode: 'edit',
                selector:  '.field[data-name="currentPassword"]',
                defs: {
                    name: 'currentPassword',
                    params: {
                        required: true,
                    },
                },
            });

            this.createView('password', 'views/user/fields/password', {
                model: user,
                mode: 'edit',
                selector: '.field[data-name="password"]',
                defs: {
                    name: 'password',
                    params: {
                        required: true,
                    },
                },
            });

            this.createView('passwordConfirm', 'views/fields/password', {
                model: user,
                mode: 'edit',
                selector: '.field[data-name="passwordConfirm"]',
                defs: {
                    name: 'passwordConfirm',
                    params: {
                        required: true,
                    },
                },
            });
        });

        this.wait(promise);
    }

    /**
     * @param {string} field
     * @return {module:views/fields/base}
     */
    getFieldView(field) {
        return this.getView(field);
    }

    // noinspection JSUnusedGlobalSymbols
    actionChange() {
        this.getFieldView('currentPassword').fetchToModel();
        this.getFieldView('password').fetchToModel();
        this.getFieldView('passwordConfirm').fetchToModel();

        const notValid =
            this.getFieldView('currentPassword').validate() ||
            this.getFieldView('password').validate() ||
            this.getFieldView('passwordConfirm').validate();

        if (notValid) {
            return;
        }

        this.$el.find('button[data-name="change"]').addClass('disabled');

        Espo.Ajax
            .putRequest('UserSecurity/password', {
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
    }
}

export default ChangePasswordModalView;
