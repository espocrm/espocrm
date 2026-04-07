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

import BaseFieldView from 'views/fields/base';

/**
 * @since 10.0.0
 */
class ColorFieldView extends BaseFieldView {
    // language=Handlebars
    listTemplateContent = `
        {{#if isNotNull}}
            <span class="fas fa-square" style="color: {{color}}"></span>
        {{/if}}
    `

    // language=Handlebars
    detailTemplateContent = `
        {{#if isNotNull}}
            <span class="fas fa-square" style="color: {{color}}"></span>
        {{else}}
            {{#if valueIsSet}}
                <span class="none-value">{{translate 'None'}}</span>
            {{else}}
                <span class="loading-value"></span>
            {{/if}}
        {{/if}}
    `

    // language=Handlebars
    editTemplateContent = `
        <div class="btn-group">
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                <span class="fas fa-square" style="color: {{color}}"></span>
            </button>
            <ul class="dropdown-menu">
                <li>
                    <ul
                        style="
                            display: grid;
                            grid-template-columns: 52px 52px 52px;
                            padding-left: 0;
                        "
                    >
                        {{#each colors}}
                            <li style="list-style: none;">
                                <a
                                    style="
                                        width: 100%;
                                        display: inline-block;
                                        text-align: center;
                                        padding: 2px;
                                    "
                                    class="dropdown-item"
                                    role="button"
                                    tabindex="0"
                                    data-action="selectColor{{#if selected}} active{{/if}}"
                                    data-value="{{value}}"
                                ><span class="fas fa-square" style="color: {{color}}"></span></a>
                            </li>
                        {{/each}}
                    </ul>
                </li>
                <li class="divider"></li>
                <li>
                    <a
                        class="dropdown-item"
                        role="button"
                        tabindex="0"
                        data-action="selectColor{{#if isNull}} active{{/if}}"
                        data-value=""
                        style="text-align: center;"
                    >{{translate 'None'}}</a>
                </li>
            </ul>
        </div>
    `

    // noinspection JSCheckFunctionSignatures
    data() {
        const value = this.model.attributes[this.name];

        if (this.isEditMode()) {
            const colors = [];

            let color = 'transparent';

            for (let i = 0; i < 9; i++) {
                colors.push({
                    color: this.colors[i],
                    selected: i === value,
                    value: i,
                });

                if (i === value) {
                    color = this.colors[i];
                }
            }

            return {
                colors: colors,
                color: color,
                isNull: value === null,
            };
        }

        if (this.isReadMode()) {
            return {
                valueIsSet: value !== undefined,
                isNotNull: value !== null,
                color: value != null ? this.colors[value] : 'transparent',
            };
        }

        return super.data();
    }

    setup() {
        /** @type {string[]} */
        this.colors = this.getHelper().themeManager.getParam('chartColorList') || [];

        this.addActionHandler('selectColor', (e, element) => {
            const value = element.dataset.value !== '' ?
                parseInt(element.dataset.value) : null;

            this.model.set(this.name, value, {ui: true});

            this.reRender();
        });
    }
}

export default ColorFieldView;
