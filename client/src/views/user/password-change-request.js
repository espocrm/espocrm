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

import View from 'view';
import Model from 'model';

export default class extends View {

    template = 'user/password-change-request'

    data() {
        return {
            requestId: this.options.requestId,
            notFound: this.options.notFound,
            notFoundMessage: this.notFoundMessage,
        };
    }

    setup() {
        this.addHandler('click', '#btn-submit', () => this.submit());

        const model = this.model = new Model();
        model.entityType = model.name = 'User';

        this.createView('password', 'views/user/fields/password', {
            model: model,
            mode: 'edit',
            selector: '.field[data-name="password"]',
            defs: {
                name: 'password',
                params: {
                    required: true,
                    maxLength: 255,
                },
            },
            strengthParams: this.options.strengthParams,
        });

        this.createView('passwordConfirm', 'views/fields/password', {
            model: model,
            mode: 'edit',
            selector: '.field[data-name="passwordConfirm"]',
            defs: {
                name: 'passwordConfirm',
                params: {
                    required: true,
                    maxLength: 255,
                },
            },
        });

        this.createView('generatePassword', 'views/user/fields/generate-password', {
            model: model,
            mode: 'detail',
            readOnly: true,
            selector: '.field[data-name="generatePassword"]',
            defs: {
                name: 'generatePassword',
            },
            strengthParams: this.options.strengthParams,
        });

        this.createView('passwordPreview', 'views/fields/base', {
            model: model,
            mode: 'detail',
            readOnly: true,
            selector: '.field[data-name="passwordPreview"]',
            defs: {
                name: 'passwordPreview',
            },
        });

        this.model.on('change:passwordPreview', () => this.reRender());

        const url = this.baseUrl = window.location.href.split('?')[0];

        this.notFoundMessage = this.translate('passwordChangeRequestNotFound', 'messages', 'User')
            .replace('{url}', url);
    }

    /**
     * @param {string} name
     * @return {import('views/fields/base').default}
     */
    getFieldView(name) {
        return /** @type {import('views/fields/base').default} */this.getView(name);
    }

    submit() {
        this.getFieldView('password').fetchToModel();
        this.getFieldView('passwordConfirm').fetchToModel();

        const notValid =
            this.getFieldView('password').validate() ||
            this.getFieldView('passwordConfirm').validate();

        const password = this.model.get('password');

        if (notValid) {
            return;
        }

        const $submit = this.$el.find('.btn-submit');

        $submit.addClass('disabled');

        Espo.Ajax
            .postRequest('User/changePasswordByRequest', {
                requestId: this.options.requestId,
                password: password,
            })
            .then(data => {
                this.$el.find('.password-change').remove();

                const url = data.url || this.baseUrl;

                const msg = this.translate('passwordChangedByRequest', 'messages', 'User') +
                    ' <a href="' + url + '">' + this.translate('Login', 'labels', 'User') + '</a>.';

                this.$el.find('.msg-box')
                    .removeClass('hidden')
                    .html('<span class="text-success">' + msg + '</span>');
            })
            .catch(() => {
                return $submit.removeClass('disabled');
            });
    }
}
