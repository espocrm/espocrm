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

import BaseFieldView from 'views/fields/base';
import MultiSelect from 'ui/multi-select';
import ExpandedLayoutEditItemModalFieldView from 'views/dashlets/fields/records/expanded-layout/modals/edit-item';

class ExpandedLayoutDashletFieldView extends BaseFieldView {

    // language=Handlebars
    editTemplateContent = `
        <div class="layout-container">
            {{#each rowDataList}}
                <div data-role="layoutRow">
                    <div
                        style="display: inline-block; width: calc(100% - var(--40px));"
                    >
                        <input
                            type="text"
                            value="{{value}}"
                            data-index="{{index}}"
                        >
                    </div>
                    {{#if hasEdit}}
                        <div class="btn-group pull-right">
                            <button
                                class="btn btn-text dropdown-toggle"
                                data-toggle="dropdown"
                            ><span class="caret"></span></button>
                            <ul class="dropdown-menu pull-right">
                                <li class="dropdown-header">{{translate 'Edit'}}</li>
                                {{#each itemList}}
                                    <li>
                                        <a
                                            role="button"
                                            tabindex="0"
                                            data-action="editItem"
                                            data-name="{{name}}"
                                        >{{label}}</a>
                                    </li>
                                {{/each}}
                            </ul>
                        </div>
                    {{/if}}
                </div>
            {{/each}}
        </div>
    `

    delimiter = ':,:'

    /**
     * @private
     * @type {string}
     */
    targetEntityType

    data() {
        const rowList = this.getRowList();

        const dataList = [...rowList, []].map((it, i) => ({
            index: i,
            value: it.map(subIt => subIt.name).join(this.delimiter),
            hasEdit: i < rowList.length,
            itemList: it.map(subIt => ({
                name: subIt.name,
                label: this.translate(subIt.name, 'fields', this.targetEntityType),
            })),
        }));

        return {
            rowDataList: dataList,
        }
    }

    setup() {
        this.addHandler('change', 'div[data-role="layoutRow"] input', () => {
            setTimeout(() => {
                this.trigger('change');
                this.reRender();
            }, 1);
        });

        this.addActionHandler('editItem', (event, target) => this.editItem(target.dataset.name));

        this.targetEntityType = this.model.get('entityType') ||
            this.getMetadata().get(['dashlets', this.dataObject.dashletName, 'entityType']);
    }

    /**
     * @private
     * @return {Array.<{name: string, link?: boolean, soft?: boolean, small?: boolean}>[]}
     */
    getRowList() {
        return Espo.Utils.cloneDeep((this.model.get(this.name) || {}).rows || []);
    }

    afterRenderEdit() {
        const rowList = Espo.Utils.cloneDeep(this.getRowList());

        rowList.push([]);

        const fieldDataList = this.getFieldDataList();

        rowList.forEach((row, i) => {
            const usedOtherList = [];
            const usedList = [];

            rowList.forEach((it, j) => {
                usedList.push(...it.map(it => it.name));

                if (j === i) {
                    return;
                }

                usedOtherList.push(...it.map(it => it.name));
            });

            const preparedList = fieldDataList
                .filter(it => !usedOtherList.includes(it.value))
                .map(it => {
                    if (!usedList.includes(it.value)) {
                        return it;
                    }

                    const itemData = this.getItemData(it.value) || {};

                    if (itemData.soft) {
                        it.style = 'soft';
                    }

                    if (itemData.small) {
                        it.small = true;
                    }

                    return it;
                });

            const inputElement = this.element.querySelector(`input[data-index="${i.toString()}"]`);

            /** @type {module:ui/multi-select~Options} */
            const multiSelectOptions = {
                items: preparedList,
                delimiter: this.delimiter,
                matchAnyWord: this.matchAnyWord,
                draggable: true,
            };

            MultiSelect.init(inputElement, multiSelectOptions);
        });
    }

    /**
     * @private
     * @return {{value: string, text: string}[]}
     */
    getFieldDataList() {
        const scope = this.model.get('entityType') ||
            this.getMetadata().get(['dashlets', this.dataObject.dashletName, 'entityType']);

        if (!scope) {
            return [];
        }

        const fields = this.getMetadata().get(['entityDefs', scope, 'fields']) || {};

        const forbiddenFieldList = this.getAcl().getScopeForbiddenFieldList(scope);

        const fieldList = Object.keys(fields)
            .sort((v1, v2) => {
                 return this.translate(v1, 'fields', scope)
                     .localeCompare(this.translate(v2, 'fields', scope));
            })
            .filter(item => {
                const defs = /** @type {Record} */fields[item];

                if (
                    defs.disabled ||
                    defs.listLayoutDisabled ||
                    defs.utility
                ) {
                    return false;
                }

                const layoutAvailabilityList = defs.layoutAvailabilityList;

                if (layoutAvailabilityList && !layoutAvailabilityList.includes('list')) {
                    return false;
                }

                const layoutIgnoreList = defs.layoutIgnoreList || [];

                if (layoutIgnoreList.includes('list')) {
                    return false;
                }

                if (forbiddenFieldList.indexOf(item) !== -1) {
                    return false;
                }

                return true;
            });

        const dataList = [];

        fieldList.forEach(item => {
            dataList.push({
                value: item,
                text: this.translate(item, 'fields', scope),
            });
        });

        return dataList;
    }

    /**
     * @private
     * @param {string} name
     */
    async editItem(name) {
        const inputData = this.getItemData(name);

        const view = new ExpandedLayoutEditItemModalFieldView({
            label: this.translate(name, 'fields', this.targetEntityType),
            data: inputData,
            onApply: data => this.applyItem(name, data),
        });

        await this.assignView('modal', view);
        await view.render();
    }

    /**
     * @private
     * @param {string} name
     * @return {{
     *     soft: boolean,
     *     small: boolean,
     * }}
     */
    getItemData(name) {
        /**
         * @type {{
         *     soft: boolean,
         *     small: boolean,
         * }}
         */
        let inputData;

        for (const row of this.getRowList()) {
            for (const item of row) {
                if (item.name === name) {
                    inputData = {
                        soft: item.soft || false,
                        small: item.small || false,
                    };
                }
            }
        }

        return inputData;
    }

    fetch() {
        const value = {rows: []};

        /** @type {Record.<string, Record>} */
        const params = {};

        for (const row of this.getRowList()) {
            for (const item of row) {
                params[item.name] = item;
            }
        }

        this.element.querySelectorAll('input').forEach(/** HTMLInputElement*/inputElement => {
            const row = [];

            let list = inputElement.value.split(this.delimiter);

            if (list.length === 1 && list[0] === '') {
                list = [];
            }

            if (list.length === 0) {
                return;
            }

            list.forEach(name => {
                const item = {name: name};

                if (name === 'name') {
                    item.link = true;
                }

                if (params[name]) {
                    item.soft = params[name].soft || false;
                    item.small = params[name].small || false;
                }

                row.push(item);
            });

            value.rows.push(row);
        });

        return {[this.name]: value};
    }

    /**
     * @private
     * @param {string} name
     * @param {{soft: boolean, small: boolean}} data
     */
    applyItem(name, data) {
        const rowList = this.getRowList();

        for (const row of rowList) {
            for (const item of row) {
                if (item.name === name) {
                    item.soft = data.soft;
                    item.small = data.small;
                }
            }
        }

        this.model.set(this.name, {rows: rowList}, {ui: true});

        this.reRender();
    }
}

export default ExpandedLayoutDashletFieldView;
