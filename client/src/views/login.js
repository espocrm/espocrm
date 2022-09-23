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

        /** @inheritDoc */
        events: {
            'submit #login-form': function (e) {
                e.preventDefault();

                this.login();
            },
            'click a[data-action="passwordChangeRequest"]': function () {
                this.showPasswordChangeRequest();
            },
            'keydown': function (e) {
                if (Espo.Utils.getKeyFromKeyEvent(e) === 'Control+Enter') {
                    e.preventDefault();

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
            };
        },

        /** @inheritDoc */
        setup: function () {
            this.anotherUser = this.options.anotherUser || null;
        },

        /**
         * @private
         * @return {string}
         */
        getLogoSrc: function () {
            var companyLogoId = this.getConfig().get('companyLogoId');

            if (!companyLogoId) {
                return this.getBasePath() + ('client/img/logo.png');
            }

            return this.getBasePath() + '?entryPoint=LogoImage&id='+companyLogoId;
        },

        /** @inheritDoc */
        afterRender: function () {
            this.$submit = this.$el.find('#btn-login');
            this.$username = this.$el.find('#field-userName');
            this.$password = this.$el.find('#field-password');

            if (this.options.prefilledUsername) {
                this.$username.val(this.options.prefilledUsername);
            }
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

            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

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
                'Espo-Authorization-By-Token': 'false',
                'Espo-Authorization-Create-Token-Secret': 'true',
            };

            if (this.anotherUser !== null) {
                headers['X-Another-User'] = this.anotherUser;
            }

            Espo.Ajax
                .getRequest('App/user', null, {
                    login: true,
                    headers: headers,
                })
                .then(data => {
                    Espo.Ui.notify(false);

                    if (this.anotherUser) {
                        data.anotherUser = this.anotherUser;
                    }

                    this.trigger('login', userName, data);
                })
                .catch(xhr => {
                    this.undisableForm();

                    if (xhr.status === 401) {
                        let data = xhr.responseJSON || {};
                        let statusReason = xhr.getResponseHeader('X-Status-Reason');

                        if (statusReason === 'second-step-required') {
                            xhr.errorIsHandled = true;
                            this.onSecondStepRequired(userName, password, data);

                            return;
                        }

                        this.onWrongCredentials();
                    }
                });
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
         * @public
         */
        disableForm: function () {
            this.$submit.addClass('disabled').attr('disabled', 'disabled');
        },

        /**
         * @public
         */
        undisableForm: function () {
            this.$submit.removeClass('disabled').removeAttr('disabled');
        },

        /**
         * @private
         * @param {string} userName
         * @param {string} password
         * @param {Object.<string, *>}data
         */
        onSecondStepRequired: function (userName, password, data) {
            let view = data.view || 'views/login-second-step';

            this.trigger('redirect', view, userName, password, data);
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

            Espo.Ui.error(this.translate('wrongUsernamePassword', 'messages', 'User'));
        },

        /**
         * @private
         */
        showPasswordChangeRequest: function () {
            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

            this.createView('passwordChangeRequest', 'views/modals/password-change-request', {
                url: window.location.href,
            }, view => {
                view.render();

                Espo.Ui.notify(false);
            });
        },
    });
});
