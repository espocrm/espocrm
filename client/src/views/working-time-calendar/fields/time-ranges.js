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

export default class extends BaseFieldView {

    // language=Handlebars
    listTemplateContent = `
        <div class="item-list">
        {{#each itemDataList}}
            <span class="item" data-key="{{key}}"
            >{{{var viewKey ../this}}}</span>{{#unless isLast}} &nbsp;&middot;&nbsp; {{/unless}}
        {{/each}}
        </div>
        {{#unless itemDataList.length}}
        <span class="none-value">{{translate 'None'}}</span>
        {{/unless}}
    `

    // language=Handlebars
    detailTemplateContent = `
        <div class="item-list">
        {{#each itemDataList}}
            <div class="item" data-key="{{key}}">
                {{{var viewKey ../this}}}
            </div>
        {{/each}}
        </div>
        {{#unless itemDataList.length}}
        <span class="none-value">{{translate 'None'}}</span>
        {{/unless}}
    `

    // language=Handlebars
    editTemplateContent = `
        <div class="item-list">
        {{#each itemDataList}}
            <div class="item" data-key="{{key}}">
                {{{var viewKey ../this}}}
            </div>
        {{/each}}
        </div>
        <div class="add-item-container margin-top-sm">
            <a
                role="button"
                tabindex="0"
                class="add-item"
                title="{{translate 'Add'}}"
            ><span class="fas fa-plus"></span></a>
        </div>
    `

    // noinspection JSCheckFunctionSignatures
    data() {
        const data = super.data();

        data.itemDataList = this.itemKeyList.map((key, i) => {
            return {
                key: key.toString(),
                viewKey: this.composeViewKey(key),
                isLast: i === this.itemKeyList.length - 1,
            };
        });

        // noinspection JSValidateTypes
        return data;
    }

    setup() {
        super.setup();

        this.validations = [
            () => this.validateRequired(),
            () => this.validateValid(),
        ];

        this.addHandler('click', '.add-item', () => this.addItem());

        this.addHandler('click', '.remove-item', (e, target) => {
            this.removeItem(parseInt(target.dataset.key));
        });
    }

    prepare() {
        this.initItems();

        return this.createItemViews();
    }

    initItems() {
        this.itemKeyList = [];

        this.getItemListFromModel().forEach((item, i) => {
            this.itemKeyList.push(i);
        });
    }

    /**
     * @returns {Promise}
     */
    createItemView(item, key) {
        const viewName = this.isEditMode() ?
            'views/working-time-calendar/fields/time-ranges/item-edit' :
            'views/working-time-calendar/fields/time-ranges/item-detail';

        return this.createView(
            this.composeViewKey(key),
            viewName,
            {
                value: item,
                selector: `.item[data-key="${key}"]`,
                key: key,
            }
        )
        .then(view => {
            this.listenTo(view, 'change', () => {
                this.trigger('change');
            });

            return view;
        });
    }

    /**
     * @returns {Promise}
     */
    createItemViews() {
        this.itemKeyList.forEach(key => {
            this.clearView(this.composeViewKey(key));
        });

        if (!this.model.has(this.name)) {
            return Promise.resolve();
        }

        const itemList = this.getItemListFromModel();

        const promiseList = [];

        this.itemKeyList.forEach((key, i) => {
            const item = itemList[i];

            const promise = this.createItemView(item, key);

            promiseList.push(promise);
        });

        return Promise.all(promiseList);
    }

    /**
     * @param {string} key
     * @return {import('./time-ranges/item-edit').default}
     */
    getItemView(key) {
        // noinspection JSValidateTypes
        return this.getView(this.composeViewKey(key));
    }

    composeViewKey(key) {
        return `item-${key}`;
    }

    /**
     * @return {[string|null, string|null][]}
     */
    getItemListFromModel() {
        return Espo.Utils.cloneDeep(this.model.get(this.name) || []);
    }

    addItem() {
        const itemList = this.getItemListFromModel();

        let value = null;

        if (itemList.length) {
            value = itemList[itemList.length - 1][1];
        }

        const item = [value, null];

        itemList.push(item);

        let key = this.itemKeyList[this.itemKeyList.length - 1];

        if (typeof key === 'undefined') {
            key = 0;
        }

        key++;

        this.itemKeyList.push(key);

        this.$el.find('.item-list').append(
            $('<div>')
                .addClass('item')
                .attr('data-key', key)
        );

        this.createItemView(item, key)
            .then(view => view.render())
            .then(() => {
                this.trigger('change');
            });
    }

    removeItem(key) {
        const index = this.itemKeyList.indexOf(key);

        if (key === -1) {
            return;
        }

        const itemList = this.getItemListFromModel();

        this.itemKeyList.splice(index, 1);
        itemList.splice(index, 1);

        this.model.set(this.name, itemList, {ui: true});

        this.clearView(this.composeViewKey(key));

        this.$el.find(`.item[data-key="${key}"`).remove();

        this.trigger('change');
    }

    fetch() {
        const itemList = [];

        this.itemKeyList.forEach(key => {
            itemList.push(
                this.getItemView(key).fetch()
            );
        });

        const data = {};

        data[this.name] = Espo.Utils.cloneDeep(itemList);

        if (data[this.name].length === 0) {
            data[this.name] = null;
        }

        return data;
    }

    validateRequired() {
        if (!this.isRequired()) {
            return false;
        }

        if (this.getItemListFromModel().length) {
            return false;
        }

        const msg = this.translate('fieldIsRequired', 'messages')
            .replace('{field}', this.getLabelText());

        this.showValidationMessage(msg, '.add-item-container');

        return true;
    }

    validateValid() {
        if (!this.isRangesInvalid()) {
            return false;
        }

        const msg = this.translate('fieldInvalid', 'messages')
            .replace('{field}', this.getLabelText());

        this.showValidationMessage(msg, '.add-item-container');

        return true;
    }

    isRangesInvalid() {
        const itemList = this.getItemListFromModel();

        for (let i = 0; i < itemList.length; i++) {
            const item = itemList[i];

            if (this.isRangeInvalid(item[0], item[1], true)) {
                return true;
            }

            if (i === 0) {
                continue;
            }

            const prevItem = itemList[i - 1];

            if (this.isRangeInvalid(prevItem[1], item[0])) {
                return true;
            }

            if (prevItem[1] === '00:00') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param {string|null} from
     * @param {string|null} to
     * @param {boolean} [isRange]
     */
    isRangeInvalid(from, to, isRange = false) {
        if (from === null || to === null) {
            return true;
        }

        const fromNumber = parseFloat(from.replace(':', '.'));
        const toNumber = parseFloat(to.replace(':', '.'));

        if (isRange && fromNumber === toNumber && to !== '00:00') {
            return true;
        }

        if (isRange && to === '00:00' && fromNumber) {
            return false;
        }

        return fromNumber > toNumber;
    }
}
