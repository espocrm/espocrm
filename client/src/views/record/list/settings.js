/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

class RecordListSettingsView extends View {

    // language=Handlebars
    templateContent = `
        {{#if toDisplay}}
        <div class="btn-group">
            <a
                role="button"
                class="btn btn-text dropdown-toggle"
                data-toggle="dropdown"
                tabindex="0"
            ><span class="fas fa-caret-down fa-sm"></span></a>
            <ul class="dropdown-menu pull-right">
            {{#each dataList}}
                <li>
                    <a
                        role="button"
                        tabindex="0"
                        data-action="toggleColumn"
                        data-name="{{name}}"
                    ><span class="check-icon fas fa-check pull-right{{#if hidden}} hidden{{/if}}"></span><div>{{label}}</div></a>
                </li>
            {{/each}}
            {{#if isNotDefault}}
                <li class="divider"></li>
                <li>
                    <a
                        role="button"
                        tabindex="0"
                        data-action="resetToDefault"
                    >{{translate 'Reset'}}</a>
                </li>
            {{/if}}
            </ul>
        </div>
        {{/if}}
    `

    data() {
        const dataList = this.getDataList();
        const isNotDefault = dataList.find(item => item.hiddenDefault !== item.hidden) !== undefined;

        return {
            dataList: dataList,
            toDisplay: dataList.length > 0,
            isNotDefault: isNotDefault,
        };
    }

    /**
     * @param {{
     *     layoutProvider: function(): Array,
     *     helper: import('helpers/list/settings').default,
     *     entityType: string,
     *     onChange: function(),
     * }} options
     */
    constructor(options) {
        super();

        this.layoutProvider = options.layoutProvider;
        this.helper = options.helper;
        this.entityType = options.entityType;
        this.onChange = options.onChange;
    }

    setup() {
        this.addActionHandler('toggleColumn', (e, target) => this.toggleColumn(target.dataset.name));
        this.addActionHandler('resetToDefault', () => this.resetToDefault());
    }

    /**
     * @private
     * @return {{
     *     hidden: boolean,
     *     hiddenDefault: boolean,
     *     name: string,
     *     label: string,
     * }[]}
     */
    getDataList() {
        const list = this.layoutProvider() || [];
        const map = this.helper.getHiddenColumnMap() || {};

        return list.filter(item => item.name && !item.link && !item.noLabel && !item.customLabel)
            .map(item => {
                const label = item.label || item.name;
                const hidden = (item.name in map) ? map[item.name] : !!item.hidden;

                return {
                    name: item.name,
                    label: this.translate(label, 'fields', this.entityType),
                    hidden: hidden,
                    hiddenDefault: !!item.hidden,
                };
            })
    }

    /**
     * @private
     * @param {string} name
     */
    toggleColumn(name) {
        const map = /** @type {Object.<string, boolean>} */this.helper.getHiddenColumnMap() || {};

        const item = this.getDataList().find(item => item.name === name);

        const defaultValue = item ? item.hiddenDefault : false;

        map[name] = !((name in map) ? map[name] : defaultValue);

        this.helper.storeHiddenColumnMap(map);

        this.onChange();
    }

    /**
     * @private
     */
    resetToDefault() {
        this.helper.clearHiddenColumnMap();

        this.onChange();
    }
}

export default RecordListSettingsView;
