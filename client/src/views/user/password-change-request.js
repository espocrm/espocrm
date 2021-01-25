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

define('views/user/password-change-request', ['view', 'model'], function (Dep, Model) {

    return Dep.extend({

        template: 'user/password-change-request',

        data: function () {
            return {
                requestId: this.options.requestId
            };
        },

        events: {
            'click #btn-submit': function () {
                this.submit();
            }
        },

        setup: function () {
            var model = this.model = new Model;
            model.name = 'User';

            this.createView('password', 'views/user/fields/password', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="password"]',
                defs: {
                    name: 'password',
                    params: {
                        required: true,
                    },
                },
                strengthParams: this.options.strengthParams,
            });

            this.createView('passwordConfirm', 'views/fields/password', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="passwordConfirm"]',
                defs: {
                    name: 'passwordConfirm',
                    params: {
                        required: true,
                    },
                },
            });
        },

        submit: function () {
            this.getView('password').fetchToModel();
            this.getView('passwordConfirm').fetchToModel();

            var notValid = this.getView('password').validate() ||
                           this.getView('passwordConfirm').validate();

            var password = this.model.get('password');

            if (notValid) {
                return;
            }

            var $submit = this.$el.find('.btn-submit');
            $submit.addClass('disabled');

            Espo.Ajax.postRequest('User/changePasswordByRequest', {
                requestId: this.options.requestId,
                password: password,
            }).then(
                function (data) {
                    this.$el.find('.password-change').remove();

                    var url = data.url || this.getConfig().get('siteUrl');

                    var msg = this.translate('passwordChangedByRequest', 'messages', 'User');
                    msg += ' <a href="' + url + '">' + this.translate('Login', 'labels', 'User') + '</a>.';

                    this.$el.find('.msg-box').removeClass('hidden').html('<span class="text-success">' + msg + '</span>');
                }.bind(this)
            ).fail(
                function () {
                    $submit.removeClass('disabled');
                }.bind(this)
            );
        },

    });
});

