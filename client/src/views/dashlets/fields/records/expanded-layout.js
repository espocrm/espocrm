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

class ExpandedLayoutDashletFieldView extends BaseFieldView {

    // language=Handlebars
    editTemplateContent = `
        <div class="layout-container"></div>
    `

    delimiter = ':,:'

    setup() {
        this.addHandler('change', 'div[data-role="layoutRow"] input', () => {
            this.trigger('change');
            this.reRender();
        });
    }

    afterRenderEdit() {
        const containerElement = this.element.querySelector(`:scope > .layout-container`);

        let rowList = (this.model.get(this.name) || {}).rows || [];

        rowList = Espo.Utils.cloneDeep(rowList);

        rowList.push([]);

        const fieldDataList = this.getFieldDataList();

        rowList.forEach((row, i) => {
            const rowElement = this.createRowElement(row, i);

            containerElement.append(rowElement);

            const inputElement = rowElement.querySelector('input');

            /** @type {module:ui/multi-select~Options} */
            const multiSelectOptions = {
                items: fieldDataList,
                delimiter: this.delimiter,
                matchAnyWord: this.matchAnyWord,
                draggable: true,
            };

            MultiSelect.init(inputElement, multiSelectOptions);
        });
    }

    /**
     * @private
     * @param {Record[]} row
     * @param {number} i
     * @return {HTMLDivElement}
     */
    createRowElement(row, i) {
        row = row || [];

        const list = [];

        row.forEach(item => {
            list.push(item.name);
        });

        const div = document.createElement('div');
        div.dataset.role = 'layoutRow';

        const input = document.createElement('input');
        input.type = 'text';
        input.classList.add('row-' + i.toString());
        input.value = list.join(this.delimiter);

        div.append(input);

        return div;
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

    fetch() {
        const value = {
            rows: [],
        };

        this.$el.find('input').each((i, el) => {
            const row = [];
            let list = ($(el).val() || '').split(this.delimiter);

            if (list.length === 1 && list[0] === '') {
                list = [];
            }

            if (list.length === 0) {
                return;
            }

            list.forEach(item => {
                const o = {name: item};

                if (item === 'name') {
                    o.link = true;
                }

                row.push(o);
            });

            value.rows.push(row);
        });

        const data = {};

        data[this.name] = value;

        return data;
    }
}

export default ExpandedLayoutDashletFieldView;
