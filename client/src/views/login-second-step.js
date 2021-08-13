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

define('views/login-second-step', 'view', function (Dep) {

    return Dep.extend({

        template: 'login-second-step',

        views: {
            footer: {
                el: 'body > footer',
                view: 'views/site/footer'
            },
        },

        events: {
            'submit #login-form': function (e) {
                this.send();

                return;
            },
            'click [data-action="backToLogin"]': function () {
                this.trigger('back');
            },
        },

        data: function () {
            return {
                message: this.message,
            };
        },

        setup: function () {
            this.message = this.translate(this.options.loginData.message, 'messages', 'User');
        },

        send: function () {
            var code = $('[data-name="field-code"]')
                .val()
                .trim()
                .replace(/\s/g, '');

            var userName = this.options.userName;
            var password = this.options.loginData.token || this.options.password;

            var $submit = this.$el.find('#btn-send');

            if (code == '') {
                this.isPopoverDestroyed = false;

                var $el = $("#field-code");

                var message = this.getLanguage().translate('codeIsRequired', 'messages', 'User');

                $el.popover({
                    placement: 'bottom',
                    container: 'body',
                    content: message,
                    trigger: 'manual',
                }).popover('show');

                var $cell = $el.closest('.form-group');

                $cell.addClass('has-error');

                $el.one('mousedown click', () => {
                    $cell.removeClass('has-error');

                    if (this.isPopoverDestroyed) {
                        return;
                    }

                    $el.popover('destroy');

                    this.isPopoverDestroyed = true;
                });

                return;
            }

            $submit.addClass('disabled').attr('disabled', 'disabled');

            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

            Espo.Ajax
                .getRequest('App/user', {code: code}, {
                    login: true,
                    headers: {
                        'Authorization': 'Basic ' + base64.encode(userName  + ':' + password),
                        'Espo-Authorization': base64.encode(userName + ':' + password),
                        'Espo-Authorization-Code': code,
                        'Espo-Authorization-Create-Token-Secret': true,
                    },
                })
                .then(data => {
                    this.notify(false);
                    this.trigger('login', userName, data);
                })
                .catch(xhr => {
                    $submit.removeClass('disabled').removeAttr('disabled');

                    if (xhr.status === 401) {
                        this.onWrongCredentials();
                    }
                });
        },

        onWrongCredentials: function () {
            var cell = $('#login .form-group');
            cell.addClass('has-error');

            this.$el.one('mousedown click', () => {
                cell.removeClass('has-error');
            });

            Espo.Ui.error(this.translate('wrongCode', 'messages', 'User'));
        },
    });
});
