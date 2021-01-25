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

define('views/modals/auth2fa-required', 'views/modal', function (Dep) {

    return Dep.extend({

        fitHeight: true,

        noCloseButton: true,

        escapeDisabled: true,

        events: {
            'click [data-action="proceed"]': 'actionProceed',
            'click [data-action="logout"]': 'actionLogout',
        },

        templateContent: '<div class="complex-text">{{complexText viewObject.messageText}}</div>' +
            '<div class="button-container btn-group" style="margin-top: 30px">' +
            '<button class="btn btn-primary" data-action="proceed">{{translate \'Proceed\'}}</button>' +
            '<button class="btn btn-default" data-action="logout">{{translate \'Log Out\'}}</button></div>'
            ,

        setup: function () {
            this.buttonList = [];

            this.headerHtml = this.translate('auth2FARequiredHeader', 'messages', 'User');

            this.messageText = this.translate('auth2FARequired', 'messages', 'User');
        },

        actionProceed: function () {
            this.createView('dialog', 'views/user/modals/security', {
                userModel: this.getUser(),
            }, function (view) {
                view.render();

                this.listenToOnce(view, 'done', function () {
                    this.clearView('dialog');
                    this.close();
                }, this);
            }, this);
        },

        actionLogout: function () {
            this.getRouter().logout();
        },

    });
});
