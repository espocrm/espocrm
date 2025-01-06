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

class Auth2faRequiredModalView extends ModalView {

    noCloseButton = true
    escapeDisabled = true

    events = {
        'click [data-action="proceed"]': 'actionProceed',
        'click [data-action="logout"]': 'actionLogout',
    }

    // language=Handlebars
    templateContent = `
        <div class="complex-text">{{complexText viewObject.messageText}}</div>
        <div class="button-container btn-group" style="margin-top: 30px">
        <button class="btn btn-primary" data-action="proceed">{{translate 'Proceed'}}</button>
        <button class="btn btn-default" data-action="logout">{{translate 'Log Out'}}</button></div>
    `

    setup() {
        this.buttonList = [];

        this.headerText = this.translate('auth2FARequiredHeader', 'messages', 'User');
        // noinspection JSUnusedGlobalSymbols
        this.messageText = this.translate('auth2FARequired', 'messages', 'User');
    }

    actionProceed() {
        this.createView('dialog', 'views/user/modals/security', {
            userModel: this.getUser(),
        }, view => {
            view.render();

            this.listenToOnce(view, 'done', () => {
                this.clearView('dialog');
                this.close();
            });
        });
    }

    actionLogout() {
        this.getRouter().logout();
    }
}

export default Auth2faRequiredModalView;
