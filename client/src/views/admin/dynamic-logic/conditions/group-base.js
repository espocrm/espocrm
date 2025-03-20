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

export default class DynamicLogicConditionGroupBaseView extends View {

    template = 'admin/dynamic-logic/conditions/group-base'

    /**
     * @type {string}
     */
    operator

    /**
     * @type {{key: string, index: number}[]}
     */
    viewDataList

    /**
     * @type {string[]}
     */
    viewList

    /**
     * @protected
     * @type {Record}
     */
    itemData

    data() {
        return {
            viewDataList: this.viewDataList,
            operator: this.operator,
            level: this.level,
            groupOperator: this.getGroupOperator(),
        };
    }

    events = {
        /** @this {DynamicLogicConditionGroupBaseView} */
        'click > div.group-head > [data-action="remove"]': function (e) {
            e.stopPropagation();

            this.trigger('remove-item');
        },
        /** @this {DynamicLogicConditionGroupBaseView} */
        'click > div.group-bottom [data-action="addField"]': function () {
            this.actionAddField();
        },
        /** @this {DynamicLogicConditionGroupBaseView} */
        'click > div.group-bottom [data-action="addAnd"]': function () {
            this.actionAddGroup('and');
        },
        /** @this {DynamicLogicConditionGroupBaseView} */
        'click > div.group-bottom [data-action="addOr"]': function () {
            this.actionAddGroup('or');
        },
        /** @this {DynamicLogicConditionGroupBaseView} */
        'click > div.group-bottom [data-action="addNot"]': function () {
            this.actionAddGroup('not');
        },
        /** @this {DynamicLogicConditionGroupBaseView} */
        'click > div.group-bottom [data-action="addCurrentUser"]': function () {
            this.addCurrentUser();
        },
        /** @this {DynamicLogicConditionGroupBaseView} */
        'click > div.group-bottom [data-action="addCurrentUserTeams"]': function () {
            this.addCurrentUserTeams();
        },
    }

    setup() {
        this.level = this.options.level || 0;
        this.number = this.options.number || 0;
        this.scope = this.options.scope;

        this.itemData = this.options.itemData || {};
        this.viewList = [];

        const conditionList = this.conditionList = this.itemData.value || [];

        this.viewDataList = [];

        conditionList.forEach((item, i) => {
            const key = this.getKey(i);

            this.createItemView(i, key, item);
            this.addViewDataListItem(i, key);
        });
    }

    getGroupOperator() {
        if (this.operator === 'or') {
            return 'or';
        }

        return 'and';
    }

    getKey(i) {
        return `view-${this.level.toString()}-${this.number.toString()}-${i.toString()}`;
    }

    /**
     * @protected
     * @param {number} number
     * @param {string} key
     * @param {Record} item
     */
    createItemView(number, key, item) {
        this.viewList.push(key);

        this.isCurrentUser = item.attribute && item.attribute.startsWith('$user.');

        const scope = this.isCurrentUser ? 'User' : this.scope;

        item = item || {};

        const additionalData = item.data || {};

        const type = additionalData.type || item.type || 'equals';
        const field = additionalData.field || item.attribute;

        let viewName;
        let fieldType;

        if (['and', 'or', 'not'].includes(type)) {
            viewName = 'views/admin/dynamic-logic/conditions/' + type;
        } else {
            fieldType = this.getMetadata().get(['entityDefs', scope, 'fields', field, 'type']);

            if (field === 'id') {
                fieldType = 'id';
            }

            if (item.attribute === '$user.id') {
                fieldType = 'currentUser';
            }

            if (item.attribute === '$user.teamsIds') {
                fieldType = 'currentUserTeams';
            }

            if (fieldType) {
                viewName = this.getMetadata().get(['clientDefs', 'DynamicLogic', 'fieldTypes', fieldType, 'view']);
            }
        }

        if (!viewName) {
            // Ensuring the item is rendered even if the field is deleted.
            viewName = 'views/admin/dynamic-logic/conditions/field-types/base';
        }

        this.createView(key, viewName, {
            itemData: item,
            scope: scope,
            level: this.level + 1,
            selector: `[data-view-key="${key}"]`,
            number: number,
            type: type,
            field: field,
            fieldType: fieldType,
        }, (view) => {
            if (this.isRendered()) {
                view.render()
            }

            this.controlAddItemVisibility();

            this.listenToOnce(view, 'remove-item', () => {
                this.removeItem(number);
            });
        });
    }

    fetch() {
        const list = [];

        this.viewDataList.forEach(item => {
            /** @type {import('./field-types/base').default} */
            const view = this.getView(item.key);

            list.push(view.fetch());
        });

        return {
            type: this.operator,
            value: list
        };
    }

    removeItem(number) {
        const key = this.getKey(number);

        this.clearView(key);

        this.$el.find(`[data-view-key="${key}"]`).remove();
        this.$el.find(`[data-view-ref-key="${key}"]`).remove();

        let index = -1;

        this.viewDataList.forEach((data, i) => {
            if (data.index === number) {
                index = i;
            }
        });

        if (~index) {
            this.viewDataList.splice(index, 1);
        }

        this.controlAddItemVisibility();
    }

    actionAddField() {
        this.createView('modal', 'views/admin/dynamic-logic/modals/add-field', {
            scope: this.scope,
        }, view => {
            view.render();

            this.listenToOnce(view, 'add-field', field => {
                this.addField(field);

                view.close();
            });
        });
    }

    addCurrentUser() {
        const i = this.getIndexForNewItem();
        const key = this.getKey(i);

        this.addItemContainer(i);
        this.addViewDataListItem(i, key);

        this.createItemView(i, key, {
            attribute: '$user.id',
            data: {
                type: 'equals',
            },
        });
    }

    addCurrentUserTeams() {
        const i = this.getIndexForNewItem();
        const key = this.getKey(i);

        this.addItemContainer(i);
        this.addViewDataListItem(i, key);

        this.createItemView(i, key, {
            attribute: '$user.teamsIds',
            data: {
                type: 'contains',
                field: 'teams',
            },
        });
    }

    addField(field) {
        let fieldType = this.getMetadata().get(['entityDefs', this.scope, 'fields', field, 'type']);

        if (!fieldType && field === 'id') {
            fieldType = 'id';
        }

        if (!this.getMetadata().get(['clientDefs', 'DynamicLogic', 'fieldTypes', fieldType])) {
            throw new Error();
        }

        const type = this.getMetadata().get(['clientDefs', 'DynamicLogic', 'fieldTypes', fieldType, 'typeList'])[0];

        const i = this.getIndexForNewItem();
        const key = this.getKey(i);

        this.addItemContainer(i);
        this.addViewDataListItem(i, key);

        this.createItemView(i, key, {
            data: {
                field: field,
                type: type
            },
        });
    }

    getIndexForNewItem() {
        if (!this.viewDataList.length) {
            return 0;
        }

        return (this.viewDataList[this.viewDataList.length - 1]).index + 1;
    }

    /**
     * @private
     * @param {number} i
     * @param {string} key
     */
    addViewDataListItem(i, key) {
        this.viewDataList.push({
            index: i,
            key: key,
        });
    }

    /**
     * @private
     * @param {number} i
     */
    addItemContainer(i) {
        const $item = $(`<div data-view-key="${this.getKey(i)}"></div>`);
        this.$el.find('> .item-list').append($item);

        const groupOperatorLabel = this.translate(this.getGroupOperator(), 'logicalOperators', 'Admin');

        const $operatorItem =
            $(`<div class="group-operator" data-view-ref-key="${this.getKey(i)}">${groupOperatorLabel}</div>`);

        this.$el.find('> .item-list').append($operatorItem);
    }

    /**
     * @private
     * @param {'and'|'or'|'not'} operator
     */
    actionAddGroup(operator) {
        const i = this.getIndexForNewItem();
        const key = this.getKey(i);

        this.addItemContainer(i);
        this.addViewDataListItem(i, key);

        const value = operator !== 'not' ? [] : undefined;

        this.createItemView(i, key, {
            type: operator,
            value: value,
        });
    }

    afterRender() {
        this.controlAddItemVisibility();
    }

    controlAddItemVisibility() {}
}
