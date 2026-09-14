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

interface Item {
    id: string;
    name: string;
}

export default class UserConnectedAppsView extends View<{
    options: {
        id: string,
    }
}> {

    // language=Handlebars
    protected templateContent = `
        <div class="header page-header">
            <h3>
                <a href="#User">{{translate 'User' category='scopeNamesPlural'}}</a>
                <span class="breadcrumb-separator"><span></span></span>
                <a href="#User/view/{{user.id}}">{{user.name}}</a>
                <span class="breadcrumb-separator"><span></span></span>
                <span>{{translate 'Connected Apps' scope='User'}}</span>
            </h3>
        </div>
        <div>{{{record}}}</div>
    `

    recordData: {
        user: {
            id: string,
            name: string,
        },
        list: Item[]
    }

    protected data(): {[p: string]: any}  {
        return {
            user: this.recordData.user,
        };
    }

    protected setup() {

        this.wait((async () => {
            await this.fetchData();

            const state: {list: Item[]} = {
                list: this.recordData.list,
            };

            const recordView = new RecordView({
                state: state,
                user: {
                    id: this.options.id,
                },
                onUpdate: async () => {
                    await this.fetchData();

                    state.list = this.recordData.list;

                    await recordView.reRender();

                    Ui.success(this.translate('Done'));
                },
            });

            await this.assignView('record', recordView);
        })());
    }

    private async fetchData() {
        Ui.notifyWait();

        this.recordData = await Ajax.getRequest(`User/${this.options.id}/connectedApps`);

        Ui.notify();
    }
}

class RecordView extends View<{
    options: {
        user: {
            id: string,
        },
        state: {
            list: Item[],
        },
        onUpdate: () => {},
    },
}> {

    // language=Handlebars
    protected templateContent = `
        {{#if list.length}}
            <ul class="list-group list-group-panel" style="max-width: var(--600px)">
                {{#each list}}
                    <li class="list-group-item">
                        <div style="display: flex;">
                            <div class="detail-field-container">{{name}}</div>
                            <div style="margin-inline-start: auto;">
                                <button
                                    class="btn btn-default"
                                    data-action="disconnect"
                                    data-id="{{id}}"
                                    {{#if ../isDisabled}}
                                        disabled="disabled"
                                    {{/if}}
                                >{{translate 'Disconnect'}}</button>
                            </div>
                        </div>
                    </li>
                {{/each}}
            </ul>
        {{else}}
            <div class="no-data">{{translate 'No Data'}}</div>
        {{/if}}
    `

    isDisabled: boolean = false

    protected data(): Record<string, any> {
        return {
            isDisabled: this.isDisabled,
            list: this.options.state.list,
        };
    }

    protected setup() {
        this.addActionHandler('disconnect', (_, target) => {
            const id = target.dataset.id as string;

            this.handleDisconnect(id);
        });
    }

    private async handleDisconnect(id: string) {
        this.isDisabled = true;

        await this.reRender();

        Ui.notifyWait();

        try {
            await Ajax.deleteRequest(`User/${this.options.user.id}/connectedApps/${id}`);
        } catch (e) {
            this.isDisabled = false;
            await this.reRender();

            return;
        }

        Ui.notify();

        this.isDisabled = false;
        await this.reRender();

        this.options.onUpdate();
    }
}
