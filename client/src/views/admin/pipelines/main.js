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

import MainView from 'views/main';

export default class AdminPipelinesMainView extends MainView {

    // language=Handlebars
    templateContent = `
        <div class="header page-header">{{{header}}}</div>
        <div class="record">
            {{#if entityTypeDataList.length}}
                <ul class="list-group list-group-panel">
                    {{#each entityTypeDataList}}
                        <li class="list-group-item">
                            <a href="{{link}}">{{label}}</a>
                        </li>
                    {{/each}}
                </ul>
            {{else}}
                <div class="panel panel-info">
                    <div class="panel-body">
                        {{complexText noMessage}}
                    </div>
                </div>
            {{/if}}
        </div>
    `

    /**
     * @private
     * @type {string[]}
     */
    entityTypeList

    data() {
        return {
            entityTypeDataList: this.getEntityTypeDataList(),
            noMessage: this.translate('noPipelinesEnabled', 'messages', 'Admin')
        }
    }

    setup() {
        this.createView('header', 'views/header', {});

        this.entityTypeList = this.getMetadata().getScopeEntityList()
            .filter(scope => this.getMetadata().get(`scopes.${scope}.pipelines`));
    }

    /**
     * @private
     * @return {Record[]}
     */
    getEntityTypeDataList() {
        return this.entityTypeList.map(it => {
            return {
                name: it,
                label: this.translate(it, 'scopeNamesPlural'),
                link: `#Admin/pipelines/scope=${it}`,
            };
        });
    }

    getHeader() {
        return this.buildHeaderHtml([
            (() => {
                const a = document.createElement('a');
                a.textContent = this.translate('Administration');
                a.href = '#Admin';

                return a;
            })(),
            (() => {
                const span = document.createElement('span');
                span.textContent = this.translate('Pipelines', 'labels', 'Admin');

                return span;
            })(),
        ]);
    }
}
