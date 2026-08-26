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
import Utils from 'utils';
import Ui from 'ui';
import Ajax from 'ajax';

class LoginSecondStepView extends View {

    /** @inheritDoc */
    template = 'login-second-step'

    /** @inheritDoc */
    views =  {
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
     * Response from the first step.
     *
     * @type {Object.<string, *>}
     * @private
     */
    loginData =  null

    /**
     * Headers composed in the first step.
     *
     * @type {Object.<string, string>}
     * @private
     */
    headers =  null

    /** @private */
    isPopoverDestroyed = false

    /**
     * @private
     * @type {HTMLInputElement}
     */
    codeElement

    /**
     * @private
     * @type {HTMLButtonElement}
     */
    submitElement

    data() {
        return {
            message: this.message,
        };
    }

    setup() {
        this.message = this.translate(this.options.loginData.message, 'messages', 'User');
        this.anotherUser = this.options.anotherUser || null;
        this.headers = this.options.headers || {};
        this.loginData = this.options.loginData;

        this.addHandler('submit', '#login-form', e => {
            e.preventDefault();

            this.send();
        });

        this.addHandler('keydown', '', e => {
            if (Utils.getKeyFromKeyEvent(e) === 'Control+Enter') {
                e.preventDefault();

                this.send();
            }
        });

        this.addActionHandler('backToLogin', () => this.trigger('back'));

    }

    afterRender() {
        this.codeElement = this.element.querySelector('[data-name="field-code"]');
        this.submitElement = this.element.querySelector('#btn-send');

        this.codeElement.focus();
    }

    /**
     * @private */
    send() {
        const code = this.codeElement.value.trim().replace(/\s/g, '');

        const userName = this.options.userName;
        const headers = Utils.clone(this.headers);

        if (code === '') {
            this.processEmptyCode();

            return;
        }

        this.disableForm();

        headers['Espo-Authorization-Code'] = code;
        headers['Espo-Authorization-Create-Token-Secret'] = 'true';

        if (this.anotherUser !== null) {
            headers['X-Another-User'] = this.anotherUser;
        }

        this.notifyLoading();

        Ajax
            .getRequest('App/user', null, {
                login: true,
                headers: headers,
            })
            .then(data => {
                Ui.notify(false);

                this.triggerLogin(userName, data);
            })
            .catch(xhr => {
                this.enableForm();


                if (xhr.status === 401) {
                    const statusReason = xhr.getResponseHeader('X-Status-Reason');

                    if (statusReason === 'error') {
                        this.onError();

                        return;
                    }

                    this.onWrongCredentials();
                }
            });
    }

    /**
     * Trigger login to proceed to the application.
     *
     * @private
     * @param {string} userName A username.
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
    processEmptyCode() {
        this.isPopoverDestroyed = false;

        const message = this.getLanguage().translate('codeIsRequired', 'messages', 'User');

        const popover = Ui.popover(this.codeElement, {
            placement: 'bottom',
            container: 'body',
            content: message,
            trigger: 'manual',
            noToggleInit: true,
        }, this);

        popover.show();

        const cellElement = this.codeElement.closest('.form-group');

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
    onError() {
        this.onFail('loginError');
    }

    /**
     * @private
     */
    onWrongCredentials() {
        this.onFail('wrongCode');
    }

    /**
     * @private
     */
    notifyLoading() {
        Ui.notifyWait();
    }

    /**
     * @private
     */
    disableForm() {
        this.submitElement.classList.add('disabled');
        this.submitElement.setAttribute('disabled', 'disabled');
    }

    /**
     * @private
     */
    enableForm() {
        this.submitElement.classList.remove('disabled');
        this.submitElement.removeAttribute('disabled');
    }
}

// noinspection JSUnusedGlobalSymbols
export default LoginSecondStepView;
