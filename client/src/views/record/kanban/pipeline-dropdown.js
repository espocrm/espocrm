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

export default class KanbanPipelineDropdownView extends View {

    // language=Handlebars
    templateContent = `
        <button
            class="btn btn-text dropdown-toggle btn-s-wide"
            data-toggle="dropdown"
            title="{{translate 'pipeline' category='fields'}}"
        >
            {{~#if 0}}{{/if~}}
            <span
                class="color-icon fas fa-square text-soft"
                style=" {{#if color}} color: {{color}} {{/if}} "
            ></span>
            <span>{{name}}</span>
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu">
            {{#each pipelines}}
                <li>
                    <a
                        role="button"
                        data-id="{{id}}"
                        data-action="selectPipeline"
                    >

                        {{#if selected}}
                            <span class="fas fa-check check-icon pull-right"></span>
                        {{/if}}
                        <div class="{{#if active}} text-bold text-soft{{/if}}">
                            {{~#if 0}}{{/if~}}
                            <span
                                class="color-icon fas fa-square text-soft"
                                style="
                                    {{#if itemColor}} color: {{itemColor}}; {{/if}}
                                    padding-right: var(--4px);
                                "
                            ></span>
                            {{name}}
                        </div>
                    </a>
                </li>
            {{/each}}
        </ul>
    `

    /**
     *
     * @param {{
     *     state: {
     *         pipelineId: string,
     *         pipelines: {id: string, name: string}[],
     *     },
     *     onChange: function(string),
     * }} options
     */
    constructor(options) {
        super(options);

        this.options = options;

        /** @private */
        this.state = options.state;
    }

    data() {
        const name = this.state.pipelines.find(it => it.id === this.state.pipelineId)?.name;

        return {
            name: name ?? this.state.pipelineId,
            pipelines: this.state.pipelines.map(it => ({
                ...it,
                selected: this.state.pipelineId === it.id,
            })),
        };
    }

    setup() {
        this.addActionHandler('selectPipeline', (e, target) => this.selectPipeline(target.dataset.id));
    }

    /**
     * @private
     * @param {string} id
     */
    selectPipeline(id) {
        if (this.state.pipelineId === id) {
            return;
        }

        this.options.onChange(id);
    }
}
