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

define('views/login', ['view'], function (Dep) {

    /**
     * @class
     * @name Class
     * @extends module:view.Class
     * @memberOf module:views/login
     */
    return Dep.extend(/** @lends module:views/login.Class# */{

        /** @inheritDoc */
        template: 'login',

        /** @inheritDoc */
        views: {
            footer: {
                el: 'body > footer',
                view: 'views/site/footer',
            },
        },

        /**
         * @type {?string}
         * @private
         */
        anotherUser: null,

        /** @private */
        isPopoverDestroyed: false,

        /**
         * @type {module:handlers/login.Class}
         * @private
         */
        handler: null,

        /**
         * @type {boolean}
         * @private
         */
        fallback: false,

        /**
         * @type {?string}
         * @private
         */
        method: null,

        /** @inheritDoc */
        events: {
            'submit #login-form': function (e) {
                e.preventDefault();

                this.login();
            },
            'click #sign-in': function (e) {
                this.signIn();
            },
            'click a[data-action="passwordChangeRequest"]': function () {
                this.showPasswordChangeRequest();
            },
            'click a[data-action="showFallback"]': function () {
                this.showFallback();
            },
            'keydown': function (e) {
                if (Espo.Utils.getKeyFromKeyEvent(e) === 'Control+Enter') {
                    e.preventDefault();

                    if (
                        this.handler &&
                        (!this.fallback || !this.$username.val())
                    ) {
                        this.signIn();

                        return;
                    }

                    this.login();
                }
            },
        },

        /** @inheritDoc */
        data: function () {
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
        },

        /** @inheritDoc */
        setup: function () {
            this.anotherUser = this.options.anotherUser || null;

            let loginData = this.getConfig().get('loginData') || {};

            this.fallback = !!loginData.fallback;
            this.method = loginData.method;

            if (loginData.handler) {
                this.wait(
                    Espo.loader
                        .requirePromise(loginData.handler)
                        .then(Handler => {
                            this.handler = new Handler(this, loginData.data || {});
                        })
                );

                this.signInText = this.getLanguage().has(this.method, 'signInLabels', 'Global') ?
                    this.translate(this.method, 'signInLabels') :
                    this.translate('Sign in');
            }

            if (this.getLanguage().has('Log in', 'labels', 'Global')) {
                this.logInText = this.translate('Log in');
            }

            this.logInText = this.getLanguage().has('Log in', 'labels', 'Global') ?
                this.translate('Log in') :
                this.translate('Login');
        },

        /**
         * @private
         * @return {string}
         */
        getLogoSrc: function () {
            let companyLogoId = this.getConfig().get('companyLogoId');

            if (!companyLogoId) {
                return this.getBasePath() + ('client/img/logo.svg');
            }

            return this.getBasePath() + '?entryPoint=LogoImage&id=' + companyLogoId;
        },

        /** @inheritDoc */
        afterRender: function () {
            this.$submit = this.$el.find('#btn-login');
            this.$signIn = this.$el.find('#sign-in');
            this.$username = this.$el.find('#field-userName');
            this.$password = this.$el.find('#field-password');

            if (this.options.prefilledUsername) {
                this.$username.val(this.options.prefilledUsername);
            }

            if (this.handler) {
                this.$username.closest('.cell').addClass('hidden');
                this.$password.closest('.cell').addClass('hidden');
                this.$submit.closest('.cell').addClass('hidden');
            }
        },

        /**
         * @private
         */
        signIn: function () {
            this.disableForm();

            this.handler
                .process()
                .then(headers => {
                    this.proceed(headers);
                })
                .catch(() => {
                    this.undisableForm();
                })
        },

        /**
         * @private
         */
        login: function () {
            let authString;
            let userName = this.$username.val();
            let password = this.$password.val();

            let trimmedUserName = userName.trim();

            if (trimmedUserName !== userName) {
                this.$username.val(trimmedUserName);

                userName = trimmedUserName;
            }

            if (userName === '') {
                this.processEmptyUsername();

                return;
            }

            this.disableForm();

            try {
                authString = Base64.encode(userName  + ':' + password);
            }
            catch (e) {
                Espo.Ui.error(this.translate('Error') + ': ' + e.message, true);

                this.undisableForm();

                throw e;
            }

            let headers = {
                'Authorization': 'Basic ' + authString,
                'Espo-Authorization': authString,
            };

            this.proceed(headers, userName, password);
        },

        /**
         * @private
         * @param {Object.<string, string>} headers
         * @param {string} [userName]
         * @param {string} [password]
         */
        proceed: function (headers, userName, password) {
            headers = Espo.Utils.clone(headers);

            let initialHeaders = Espo.Utils.clone(headers);

            headers['Espo-Authorization-By-Token'] = 'false';
            headers['Espo-Authorization-Create-Token-Secret'] = 'true';

            if (this.anotherUser !== null) {
                headers['X-Another-User'] = this.anotherUser;
            }

            this.notifyLoading();

            Espo.Ajax
                .getRequest('App/user', null, {
                    login: true,
                    headers: headers,
                })
                .then(data => {
                    Espo.Ui.notify(false);

                    this.triggerLogin(userName, data);
                })
                .catch(xhr => {
                    this.undisableForm();

                    if (xhr.status === 401) {
                        let data = xhr.responseJSON || {};
                        let statusReason = xhr.getResponseHeader('X-Status-Reason');

                        if (statusReason === 'second-step-required') {
                            xhr.errorIsHandled = true;
                            this.onSecondStepRequired(initialHeaders, userName, password, data);

                            return;
                        }

                        this.onWrongCredentials();
                    }
                });
        },

        /**
         * Trigger login to proceed to the application.
         *
         * @private
         * @param {?string} userName A username.
         * @param {Object.<string, *>} data Data returned from the `App/user` request.
         */
        triggerLogin: function (userName, data) {
            if (this.anotherUser) {
                data.anotherUser = this.anotherUser;
            }

            if (!userName) {
                userName = (data.user || {}).userName;
            }

            this.trigger('login', userName, data);
        },

        /**
         * @private
         */
        processEmptyUsername: function () {
            this.isPopoverDestroyed = false;

            let $el = this.$username;

            let message = this.getLanguage().translate('userCantBeEmpty', 'messages', 'User');

            $el
                .popover({
                    placement: 'bottom',
                    container: 'body',
                    content: message,
                    trigger: 'manual',
                })
                .popover('show');

            let $cell = $el.closest('.form-group');

            $cell.addClass('has-error');

            $el.one('mousedown click', () => {
                $cell.removeClass('has-error');

                if (this.isPopoverDestroyed) {
                    return;
                }

                $el.popover('destroy');

                this.isPopoverDestroyed = true;
            });
        },

        /**
         * @private
         */
        disableForm: function () {
            this.$submit.addClass('disabled').attr('disabled', 'disabled');
            this.$signIn.addClass('disabled').attr('disabled', 'disabled');
        },

        /**
         * @private
         */
        undisableForm: function () {
            this.$submit.removeClass('disabled').removeAttr('disabled');
            this.$signIn.removeClass('disabled').removeAttr('disabled');
        },

        /**
         * @private
         * @param {Object.<string, string>} headers
         * @param {string} userName
         * @param {string} password
         * @param {Object.<string, *>} data
         */
        onSecondStepRequired: function (headers, userName, password, data) {
            let view = data.view || 'views/login-second-step';

            this.trigger('redirect', view, headers, userName, password, data);
        },

        /**
         * @private
         */
        onWrongCredentials: function () {
            let $cell = $('#login .form-group');

            $cell.addClass('has-error');

            this.$el.one('mousedown click', () => {
                $cell.removeClass('has-error');
            });

            let messageKey = this.handler ?
                'failedToLogIn' :
                'wrongUsernamePassword';

            Espo.Ui.error(this.translate(messageKey, 'messages', 'User'));
        },

        /**
         * @private
         */
        showFallback: function () {
            this.$el.find('[data-action="showFallback"]').addClass('hidden');

            this.$el.find('.panel-body').addClass('fallback-shown');

            this.$username.closest('.cell').removeClass('hidden');
            this.$password.closest('.cell').removeClass('hidden');
            this.$submit.closest('.cell').removeClass('hidden');
        },

        /**
         * @private
         */
        notifyLoading: function () {
            Espo.Ui.notify(' ... ');
        },

        /**
         * @private
         */
        showPasswordChangeRequest: function () {
            this.notifyLoading();

            this.createView('passwordChangeRequest', 'views/modals/password-change-request', {
                url: window.location.href,
            }, view => {
                view.render();

                Espo.Ui.notify(false);
            });
        },
    });
});
