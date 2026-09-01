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
import Ajax from 'ajax';
import Ui from 'ui';

// noinspection JSUnusedGlobalSymbols
export default class AuthorizationConsentView extends View<{
    options: {}
}> {

    // language=Handlebars
    protected templateContent = `
        <div class="block-center-5 margin-top">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="alert alert-info">
                        {{complexText labels.info}}
                    </div>
                    <ul class="list-group">
                        {{#each scopeDataList}}
                            <li class="list-group-item">{{label}}</li>
                        {{/each}}
                    </ul>
                    <div class="margin-top-2x center-align">
                        <button
                            class="btn btn-danger btn-x-wide pull-left"
                            data-action="allow"
                        >{{labels.allow}}</button>
                        <button
                            class="btn btn-default btn-x-wide pull-right"
                            data-action="cancel"
                        >{{labels.cancel}}</button>
                    </div>
                </div>
            </div>
        </div>
    `
    params: {
        clientId: string | null,
        redirectUri: string | null,
    }

    consentData: {
        scopeDataList: {
            name: string,
            label: string,
        }[],
        labels: Record<string, string>,
    }

    protected data() {
        return {
            ...this.consentData,
        };
    }

    protected setup() {
        this.addActionHandler('allow', () => this.handleAllow());
        this.addActionHandler('cancel', () => this.handleCancel());

        const params = new URLSearchParams(window.location.search);

        this.params = {
            clientId: params.get('client_id'),
            redirectUri: params.get('redirect_uri'),
        };

        this.wait(this.loadData());

        // @todo Pass filtered scopes to the next step.
    }

    private async loadData() {
        Ui.notifyWait();

        this.consentData = await Ajax.getRequest(`OAuthServer/consentData`, {
            clientId: this.params.clientId,
        }) as AuthorizationConsentView['consentData'];

        Ui.notify();
    }

    private handleAllow() {

    }

    private handleCancel() {

    }
}
