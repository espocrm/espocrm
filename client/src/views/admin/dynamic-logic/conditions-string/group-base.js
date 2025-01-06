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

export default class DynamicLogicConditionsStringGroupBaseView extends View {

    template = 'admin/dynamic-logic/conditions-string/group-base'

    /**
     * @type {number}
     */
    level

    /**
     * @type {string}
     */
    scope

    /**
     * @type {number}
     */
    number

    /**
     * @type {string}
     */
    operator

    /**
     * @type {Record}
     */
    itemData

    /**
     * @type {Record}
     */
    additionalData

    /**
     * @type {string[]}
     */
    viewList

    /**
     * @type {{key: string, isEnd: boolean}[]}
     */
    viewDataList

    data() {
        if (!this.conditionList.length) {
            return {
                isEmpty: true
            };
        }

        return {
            viewDataList: this.viewDataList,
            operator: this.operator,
            level: this.level
        };
    }

    setup() {
        this.level = this.options.level || 0;
        this.number = this.options.number || 0;
        this.scope = this.options.scope;

        this.operator = this.options.operator || this.operator;

        this.itemData = this.options.itemData || {};
        this.viewList = [];

        const conditionList = this.conditionList = this.itemData.value || [];

        this.viewDataList = [];

        conditionList.forEach((item, i) => {
            const key = `view-${this.level.toString()}-${this.number.toString()}-${i.toString()}`;

            this.createItemView(i, key, item);
            this.viewDataList.push({
                key: key,
                isEnd: i === conditionList.length - 1,
            });
        });
    }

    getFieldType(item) {
        return this.getMetadata()
            .get(['entityDefs', this.scope, 'fields', item.attribute, 'type']) || 'base';
    }

    /**
     *
     * @param {number} number
     * @param {string} key
     * @param {{data?: Record, type?: string}} item
     */
    createItemView(number, key, item) {
        this.viewList.push(key);

        item = item || {};

        const additionalData = item.data || {};

        const type = additionalData.type || item.type || 'equals';
        const fieldType = this.getFieldType(item);

        const viewName = this.getMetadata()
            .get(['clientDefs', 'DynamicLogic', 'fieldTypes', fieldType, 'conditionTypes', type, 'itemView']) ||
            this.getMetadata()
                .get(['clientDefs', 'DynamicLogic', 'itemTypes', type, 'view']);

        if (!viewName) {
            return;
        }

        const operator = this.getMetadata()
            .get(['clientDefs', 'DynamicLogic', 'itemTypes', type, 'operator']);

        let operatorString = this.getMetadata()
            .get(['clientDefs', 'DynamicLogic', 'itemTypes', type, 'operatorString']);

        if (!operatorString) {
            operatorString = this.getLanguage()
                .translateOption(type, 'operators', 'DynamicLogic')
                .toLowerCase();

            operatorString = '<i class="small">' + operatorString + '</i>';
        }

        this.createView(key, viewName, {
            itemData: item,
            scope: this.scope,
            level: this.level + 1,
            selector: `[data-view-key="${key}"]`,
            number: number,
            operator: operator,
            operatorString: operatorString,
        });
    }
}
