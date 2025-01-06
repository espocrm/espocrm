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
            ><span class="caret"></span></a>
            <ul class="dropdown-menu pull-right">
                {{#if dataList.length}}
                <li class="dropdown-header">{{fieldsLabel}}</li>
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
                {{/if}}
            {{#if hasColumnResize}}
                <li class="divider"></li>
                <li>
                    <a
                        role="button"
                        tabindex="0"
                        data-action="toggleColumnResize"
                    >
                        <span class="check-icon fas fa-check pull-right {{#unless columnResize}} hidden {{/unless}}"></span>
                        <div>{{translate 'Column Resize'}}</div></a>
                </li>
            {{/if}}
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
        const columnResize = this.helper.getColumnResize();
        const dataList = this.getDataList();
        const hasColumnResize = this.columnResize && (columnResize || this.isColumnResizeApplicable());

        const isNotDefault =
            dataList.find(item => item.hiddenDefault !== item.hidden) !== undefined ||
            Object.keys(this.helper.getColumnWidthMap()).length > 0;

        return {
            dataList: dataList,
            toDisplay: dataList.length > 0 || columnResize,
            isNotDefault: isNotDefault,
            fieldsLabel: this.translate('Fields'),
            hasColumnResize: hasColumnResize,
            columnResize: columnResize,
        };
    }

    /**
     * @typedef {Object} RecordListSettingsView~onChangeOptions
     * @property {'resetToDefault'|'toggleColumn'|'toggleColumnResize'} action An action.
     * @property {string} [column] A column.
     */

    /**
     * @param {{
     *     layoutProvider: function(): {
     *         name: string,
     *         width?: number,
     *         widthPx?: number,
     *         label?: string,
     *         customLabel?: string,
     *         noLabel?: boolean,
     *         hidden?: boolean,
     *     }[],
     *     helper: import('helpers/list/settings').default,
     *     entityType: string,
     *     onChange: function(RecordListSettingsView~onChangeOptions),
     *     columnResize?: boolean,
     * }} options
     */
    constructor(options) {
        super();

        this.layoutProvider = options.layoutProvider;
        this.helper = options.helper;
        this.entityType = options.entityType;
        this.onChange = options.onChange;
        this.columnResize = options.columnResize || false;
    }

    setup() {
        this.addActionHandler('toggleColumn', (e, target) => this.toggleColumn(target.dataset.name));
        this.addActionHandler('toggleColumnResize', () => this.toggleColumnResize());
        this.addActionHandler('resetToDefault', () => this.resetToDefault());

        /** @private */
        this.onColumnWidthChangeBind = this.onColumnWidthChange.bind(this);

        this.helper.subscribeToColumnWidthChange(this.onColumnWidthChangeBind);

        if (window.innerWidth < this.getThemeManager().getParam('screenWidthXs')) {
            this.columnResize = false;
        }
    }

    onRemove() {
        this.helper.unsubscribeFromColumnWidthChange(this.onColumnWidthChangeBind);
    }

    /**
     * @private
     */
    onColumnWidthChange() {
        this.reRender();
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
     * @return {boolean}
     */
    isColumnResizeApplicable() {
        const list = this.layoutProvider().filter(it => {
            return !this.helper.isColumnHidden(it.name, it.hidden);
        });

        if (!list || list.length <= 1) {
            return false;
        }

        if (!list.find(it => !it.widthPx && !it.width)) {
            return false;
        }

        return !!list.find(it => it.widthPx || it.width);
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

        this.onChange({action: 'toggleColumn', column: name});
    }

    /**
     * @private
     */
    toggleColumnResize() {
        const value = !this.helper.getColumnResize();

        this.helper.storeColumnResize(value);

        this.onChange({action: 'toggleColumnResize'});
    }

    /**
     * @private
     */
    resetToDefault() {
        this.helper.clearHiddenColumnMap();
        this.helper.clearColumnWidthMap();

        this.onChange({action: 'resetToDefault'});
    }
}

export default RecordListSettingsView;
