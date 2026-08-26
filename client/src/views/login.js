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
import Base64 from 'js-base64';
import Utils from 'utils';
import Ui from 'ui';
import Ajax from 'ajax';

class LoginView extends View {

    template = 'login'

    views = {
        footer: {
            fullSelector: 'body > footer',
            view: 'views/site/footer',
        },
    }

    /**
     * @type {string|null}
     * @private
     */
    anotherUser = null

    /**
     * @private
     */
    isPopoverDestroyed = false

    /**
     * @type {module:handlers/login}
     * @private
     */
    handler = null

    /**
     * @type {boolean}
     * @private
     */
    fallback = false

    /**
     * @type {string|null}
     * @private
     */
    method = null

    /**
     * @private
     * @type {HTMLInputElement}
     */
    usernameElement

    /**
     * @private
     * @type {HTMLInputElement}
     */
    passwordElement

    /**
     * @private
     * @type {HTMLButtonElement}
     */
    submitElement

    /**
     * @private
     * @type {HTMLButtonElement|null}
     */
    signInElement

    /**
     * @private
     * @type {HTMLButtonElement|null}
     */
    showFallbackElement

    data() {
        return {
            logoSrc: this.getLogoSrc(),
            showForgotPassword: this.getConfig().get('passwordRecoveryEnabled'),
            anotherUser: this.anotherUser,
            hasSignIn: !!this.handler,
            hasFallback: !!this.handler && this.fallback,
            method: this.method,
            signInText: this.signInText,
            logInText: this.logInText,
        };
    }

    setup() {
        this.addHandler('submit', '#login-form', e => {
            e.preventDefault();

            this.login();
        });

        this.addHandler('keydown', '', e => {
            if (Utils.getKeyFromKeyEvent(e) === 'Control+Enter') {
                e.preventDefault();

                if (this.handler && (!this.fallback || !this.usernameElement.value)) {
                    this.signIn();

                    return;
                }

                this.login();
            }
        });

        this.addHandler('click', '#sign-in', () => this.signIn());
        this.addActionHandler('passwordChangeRequest', () => this.showPasswordChangeRequest());
        this.addActionHandler('showFallback', () => this.showFallback());
        this.addActionHandler('toggleShowPassword', () => this.toggleShowPassword());

        this.anotherUser = this.options.anotherUser || null;

        const loginData = this.getConfig().get('loginData') || {};

        this.fallback = !!loginData.fallback;
        this.method = loginData.method;

        if (loginData.handler) {
            this.wait(
                Espo.loader.requirePromise(loginData.handler)
                    .then(Handler => {
                        this.handler = new Handler(this, loginData.data || {});
                    })
            );

            this.signInText = this.getLanguage().has(this.method, 'signInLabels', 'Global') ?
                this.translate(this.method, 'signInLabels') :
                this.translate('Sign in');
        }

        this.wait(this.getHelper().processSetupHandlers(this, 'login'));

        if (this.getLanguage().has('Log in', 'labels', 'Global')) {
            this.logInText = this.translate('Log in');
        }

        this.logInText = this.getLanguage().has('Log in', 'labels', 'Global') ?
            this.translate('Log in') :
            this.translate('Login');
    }

    /**
     * @private
     * @return {string}
     */
    getLogoSrc() {
        const companyLogoId = this.getConfig().get('companyLogoId');

        if (!companyLogoId) {
            return this.getBasePath() +
                (this.getConfig().get('logoSrc') || 'client/img/logo.svg');
        }

        return this.getBasePath() + '?entryPoint=LogoImage&id=' + companyLogoId;
    }

    afterRender() {
        this.submitElement = this.element.querySelector('#btn-login');
        this.signInElement = this.element.querySelector('#sign-in');
        this.usernameElement = this.element.querySelector('#field-userName');
        this.passwordElement = this.element.querySelector('#field-password');
        this.showFallbackElement = this.element.querySelector('[data-action="showFallback"]');

        if (this.options.prefilledUsername) {
            this.usernameElement.value = this.options.prefilledUsername;
        }

        if (this.handler) {
            this.usernameElement.closest('.cell').classList.add('hidden');
            this.passwordElement.closest('.cell').classList.add('hidden');
            this.submitElement.closest('.cell').classList.add('hidden');
        }
    }

    /**
     * @private
     */
    async signIn() {
        this.disableForm();

        let headers;

        try {
            headers = await this.handler.process();
        } catch (e) {
            this.enableForm();

            return;
        }

        await this.proceed(headers, this.options.prefilledUsername);
    }

    /**
     * @private
     */
    login() {
        let authString;

        const userName = this.usernameElement.value.trim();
        const password = this.passwordElement.value;

        this.usernameElement.value = userName;

        if (userName === '') {
            this.processEmptyUsername();

            return;
        }

        this.disableForm();

        try {
            authString = Base64.encode(userName + ':' + password);
        } catch (e) {
            Ui.error(this.translate('Error') + ': ' + e.message, true);

            this.enableForm();

            throw e;
        }

        const headers = {
            'Authorization': 'Basic ' + authString,
            'Espo-Authorization': authString,
        };

        this.proceed(headers, userName, password);
    }

    /**
     * @private
     * @param {Object.<string, string>} headers
     * @param {string} [userName]
     * @param {string} [password]
     */
    async proceed(headers, userName, password) {
        headers = Espo.Utils.clone(headers);

        const initialHeaders = Espo.Utils.clone(headers);

        headers['Espo-Authorization-By-Token'] = 'false';
        headers['Espo-Authorization-Create-Token-Secret'] = 'true';

        if (this.anotherUser !== null) {
            headers['X-Another-User'] = this.anotherUser;
        }

        Ui.notifyWait();

        let data;

        try {
            data = await Ajax.getRequest('App/user', null, {
                login: true,
                headers: headers,
            });
        } catch (xhr) {
            this.enableForm();

            if (xhr.status === 401) {
                const data = xhr.responseJSON || {};
                const statusReason = xhr.getResponseHeader('X-Status-Reason');

                if (statusReason === 'second-step-required') {
                    xhr.errorIsHandled = true;
                    this.onSecondStepRequired(initialHeaders, userName, password, data);

                    return;
                }

                if (statusReason === 'error') {
                    this.onError();

                    return;
                }

                this.onWrongCredentials();
            }

            return;
        }

        Ui.notify();

        this.triggerLogin(userName, data);
    }

    /**
     * Trigger login to proceed to the application.
     *
     * @private
     * @param {string|null} userName A username.
     * @param {Object.<string, *>} data Data returned from the `App/user` request.
     */
    triggerLogin(userName, data) {
        if (this.anotherUser) {
            data.anotherUser = this.anotherUser;
        }

        if (!userName) {
            userName = (data.user || {}).userName;
        }

        this.trigger('login', userName, data);
    }

    /**
     * @private
     */
    processEmptyUsername() {
        this.isPopoverDestroyed = false;

        const message = this.getLanguage().translate('userCantBeEmpty', 'messages', 'User');

        const popover = Ui.popover(this.usernameElement, {
            placement: 'bottom',
            container: 'body',
            content: message,
            trigger: 'manual',
            noToggleInit: true,
        }, this);

        popover.show();

        const cellElement = this.usernameElement.closest('.form-group');

        cellElement.classList.add('has-error');

        cellElement.addEventListener('mousedown', () => {
            cellElement.classList.remove('has-error');

            if (this.isPopoverDestroyed) {
                return;
            }

            popover.destroy();

            this.isPopoverDestroyed = true;
        }, {once: true});
    }

    /**
     * @private
     */
    disableForm() {
        this.submitElement.classList.add('disabled');
        this.submitElement.setAttribute('disabled', 'disabled');


        this.signInElement?.classList.add('disabled');
        this.signInElement?.setAttribute('disabled', 'disabled');
    }

    /**
     * @private
     */
    enableForm() {
        this.submitElement.classList.remove('disabled');
        this.submitElement.removeAttribute('disabled');

        this.signInElement?.classList.remove('disabled');
        this.signInElement?.removeAttribute('disabled');
    }

    /**
     * @private
     * @param {Object.<string, string>} headers
     * @param {string} userName
     * @param {string} password
     * @param {Object.<string, *>} data
     */
    onSecondStepRequired(headers, userName, password, data) {
        const view = data.view ?? 'views/login-second-step';

        this.trigger('redirect', view, headers, userName, password, data);
    }

    /**
     * @private
     */
    onError() {
        this.onFail('loginError');
    }

    /**
     * @private
     */
    onWrongCredentials() {
        const msg = this.handler ?
            'failedToLogIn' :
            'wrongUsernamePassword';

        this.onFail(msg);
    }

    /**
     * @private
     */
    onFail(message) {
        const cellElement = this.element.querySelector('#login .form-group');

        cellElement.classList.add('has-error');

        cellElement.addEventListener('mousedown', () => {
            cellElement.classList.remove('has-error');
        }, {once: true});

        Ui.error(this.translate(message, 'messages', 'User'));
    }

    /**
     * @private
     */
    showFallback() {
        this.showFallbackElement.classList.add('hidden');
        this.element.querySelector('.panel-body').classList.add('fallback-shown');

        this.usernameElement.closest('.cell').classList.remove('hidden');
        this.passwordElement.closest('.cell').classList.remove('hidden');
        this.submitElement.closest('.cell').classList.remove('hidden');
    }

    /**
     * @private
     */
    async showPasswordChangeRequest() {
        Ui.notifyWait();

        const view = await this.createView('passwordChangeRequest', 'views/modals/password-change-request', {
            url: window.location.href,
        });

        await view.render();

        Ui.notify();
    }

    /**
     * @private
     */
    toggleShowPassword() {
        const input = this.element.querySelector('[id="field-password"]');
        const button = this.element.querySelector('[data-action="toggleShowPassword"]');
        const icon = button.children[0];

        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        if (input.type === 'password') {
            input.type = 'text';

            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';

            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }


        input.focus();
        input.setSelectionRange(input.value.length, input.value.length);
    }
}

export default LoginView;
