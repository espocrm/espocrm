/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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
import Ajax from 'ajax';

export default class extends View {

    // language=Handlebars
    templateContent = `
        <div class="container content">
            <div class="block-center">
                <div class="panel panel-default password-change">
                    <div class="panel-heading">
                        <h4 class="panel-title">{{translate 'Change Password' scope='User'}}</h4>
                    </div>
                    <div class="panel-body">
                        {{#if hasForm}}
                            <div class="row">
                                <div class="cell form-group col-sm-6">
                                    <label
                                        for="login"
                                        class="control-label"
                                    >{{translate 'newPassword' category='fields' scope='User'}}</label>
                                    <div class="field" data-name="password">{{{password}}}</div>
                                </div>
                                <div class="cell form-group col-sm-6">
                                    <label
                                        class="control-label"
                                    >&nbsp;</label>
                                    <div class="field" data-name="generatePassword">{{{generatePassword}}}</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="cell form-group col-sm-6">
                                    <label
                                        for="login"
                                        class="control-label"
                                    >{{translate 'newPasswordConfirm' category='fields' scope='User'}}</label>
                                    <div class="field" data-name="passwordConfirm">{{{passwordConfirm}}}</div>
                                </div>
                                <div class="cell form-group col-sm-6">
                                    {{#if hasPasswordPreview}}
                                        <label
                                            class="control-label"
                                        >&nbsp;</label>
                                        <div
                                            class="field detail-field-container"
                                            data-name="passwordPreview"
                                        >{{{passwordPreview}}}</div>
                                    {{/if}}
                                </div>
                            </div>
                            <div>
                                <button
                                    type="button"
                                    class="btn btn-danger btn-s-wide"
                                    id="btn-submit"
                                >{{translate 'Submit'}}</button>
                            </div>
                        {{/if}}

                        {{#if notFound}}
                            <div class="complex-text">{{complexText notFoundMessage}}</div>
                        {{/if}}

                        {{#if isFinished}}
                            <div
                                class="alert alert-success"
                            >{{complexText finishMessage}}</div>
                            <div class="margin-top-2x">
                                <a
                                    class="btn btn-link"
                                    href="{{followUrl}}"
                                >{{translate 'Login' scope='User'}}</a>
                            </div>
                        {{/if}}
                    </div>
                </div>
            </div>
        </div>
    `

    /**
     * @private
     * @type {boolean}
     */
    isFinished = false

    /**
     * @private
     * @type {string | null}
     */
    followUrl = null

    data() {
        const finishMessage = this.isFinished ?
            this.translate('passwordChangedByRequest', 'messages', 'User') :
            null;

        console.log(finishMessage);

        return {
            requestId: this.options.requestId,
            notFound: this.options.notFound,
            hasForm: !this.options.notFound && !this.isFinished,
            notFoundMessage: this.notFoundMessage,
            hasPasswordPreview: this.model.attributes.passwordPreview != null,
            isFinished: this.isFinished,
            followUrl: this.followUrl,
            finishMessage: finishMessage,
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
            name: 'password',
            params: {
                required: true,
                maxLength: 255,
            },
            strengthParams: this.options.strengthParams,
        });

        this.createView('passwordConfirm', 'views/fields/password', {
            model: model,
            mode: 'edit',
            selector: '.field[data-name="passwordConfirm"]',
            name: 'passwordConfirm',
            params: {
                required: true,
                maxLength: 255,
            },
        });

        this.createView('generatePassword', 'views/user/fields/generate-password', {
            model: model,
            mode: 'detail',
            readOnly: true,
            selector: '.field[data-name="generatePassword"]',
            name: 'generatePassword',
            strengthParams: this.options.strengthParams,
        });

        this.createView('passwordPreview', 'views/fields/varchar', {
            model: model,
            mode: 'detail',
            readOnly: true,
            selector: '.field[data-name="passwordPreview"]',
            name: 'passwordPreview',
            params: {
                copyToClipboard: true,
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

    /**
     * @private
     */
    async submit() {
        this.getFieldView('password').fetchToModel();
        this.getFieldView('passwordConfirm').fetchToModel();

        const notValid =
            this.getFieldView('password').validate() ||
            this.getFieldView('passwordConfirm').validate();

        const password = this.model.attributes.password;

        if (notValid) {
            return;
        }

        const button = this.element.querySelector('#btn-submit')

        button.classList.add('disabled');
        button.setAttribute('disabled', 'disabled');

        let data;

        try {
            data = await Ajax.postRequest('User/changePasswordByRequest', {
                requestId: this.options.requestId,
                password: password,
            });
        } catch (e) {
            button.classList.remove('disabled');
            button.removeAttribute('disabled');

            return;
        }

        this.isFinished = true;
        this.followUrl = data.url ?? this.baseUrl;

        await this.reRender();
    }
}
