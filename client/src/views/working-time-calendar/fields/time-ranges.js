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

define('views/working-time-calendar/fields/time-ranges', ['views/fields/base'], function (Dep) {

    /**
     * @class
     * @name Class
     * @memberOf module:views/working-time-calendar/fields/time-ranges
     * @extends module:views/fields/base
     */
    return Dep.extend(/** @lends module:views/working-time-calendar/fields/time-ranges.Class# */{

        listTemplateContent: `
            <div class="item-list">
            {{#each itemDataList}}
                <span class="item" data-key="{{key}}"
                >{{{var viewKey ../this}}}</span>{{#unless isLast}} &nbsp;&middot;&nbsp; {{/unless}}
            {{/each}}
            </div>
            {{#unless itemDataList.length}}
            <span class="none-value">{{translate 'None'}}</span>
            {{/unless}}
        `,

        detailTemplateContent: `
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
        `,

        editTemplateContent: `
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
        `,

        fetchEmptyAsNull: false,

        validations: ['required', 'valid'],

        events: {
            'click .add-item': function () {
                this.addItem();
            },
            'click .remove-item': function (e) {
                let key = parseInt($(e.currentTarget).attr('data-key'));

                this.removeItem(key);
            },
        },

        data: function () {
            let data = Dep.prototype.data.call(this);

            data.itemDataList = this.itemKeyList.map((key, i) => {
                return {
                    key: key.toString(),
                    viewKey: this.composeViewKey(key),
                    isLast: i === this.itemKeyList.length - 1,
                };
            });

            return data;
        },

        prepare: function () {
            this.initItems();

            return this.createItemViews();
        },

        initItems: function () {
            this.itemKeyList = [];

            this.getItemListFromModel().forEach((item, i) => {
                this.itemKeyList.push(i);
            });
        },

        /**
         * @returns {Promise}
         */
        createItemView: function (item, key) {
            let viewName = this.isEditMode() ?
                'views/working-time-calendar/fields/time-ranges/item-edit' :
                'views/working-time-calendar/fields/time-ranges/item-detail';

            return this.createView(
                this.composeViewKey(key),
                viewName,
                {
                    value: item,
                    selector: '.item[data-key="' + key + '"]',
                    key: key,
                }
            )
            .then(view => {
                this.listenTo(view, 'change', () => {
                    this.trigger('change');
                });

                return view;
            });
        },

        /**
         * @returns {Promise}
         */
        createItemViews: function () {
            this.itemKeyList.forEach(key => {
                this.clearView(this.composeViewKey(key));
            });

            if (!this.model.has(this.name)) {
                return Promise.resolve();
            }

            let itemList = this.getItemListFromModel();

            let promiseList = [];

            this.itemKeyList.forEach((key, i) => {
                let item = itemList[i];

                let promise = this.createItemView(item, key);

                promiseList.push(promise);
            });

            return Promise.all(promiseList);
        },

        getItemView: function (key) {
            return this.getView(this.composeViewKey(key));
        },

        composeViewKey: function (key) {
            return 'item-' + key;
        },

        /**
         * @return {[string|null, string|null][]}
         */
        getItemListFromModel: function () {
            return Espo.Utils.cloneDeep(this.model.get(this.name) || []);
        },

        addItem: function () {
            let itemList = this.getItemListFromModel();

            let value = null;

            if (itemList.length) {
                value = itemList[itemList.length - 1][1];
            }

            let item = [value, null];

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
        },

        removeItem: function (key) {
            let index = this.itemKeyList.indexOf(key);

            if (key === -1) {
                return;
            }

            let itemList = this.getItemListFromModel();

            this.itemKeyList.splice(index, 1);
            itemList.splice(index, 1);

            this.model.set(this.name, itemList, {ui: true});

            this.clearView(this.composeViewKey(key));

            this.$el.find(`.item[data-key="${key}"`).remove();

            this.trigger('change');
        },

        fetch: function () {
            let itemList = [];

            this.itemKeyList.forEach(key => {
                itemList.push(
                    this.getItemView(key).fetch()
                );
            });

            let data = {};

            data[this.name] = Espo.Utils.cloneDeep(itemList);

            if (data[this.name].length === 0) {
                data[this.name] = null;
            }

            return data;
        },

        validateRequired: function () {
            if (!this.isRequired()) {
                return false;
            }

            if (this.getItemListFromModel().length) {
                return false;
            }

            let msg = this.translate('fieldIsRequired', 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg, '.add-item-container');

            return true;
        },

        validateValid: function () {
            if (!this.isRangesInvalid()) {
                return false;
            }

            let msg = this.translate('fieldInvalid', 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg, '.add-item-container');

            return true;
        },

        isRangesInvalid: function () {
            let itemList = this.getItemListFromModel();

            for (let i = 0; i < itemList.length; i++) {
                let item = itemList[i];

                if (this.isRangeInvalid(item[0], item[1], true)) {
                    return true;
                }

                if (i === 0) {
                    continue;
                }

                let prevItem = item[i - 1];

                if (this.isRangeInvalid(prevItem[1], item[0])) {
                    return true;
                }
            }

            return false;
        },

        /**
         * @param {string|null} from
         * @param {string|null} to
         * @param {boolean} [noEmpty]
         */
        isRangeInvalid: function (from, to, noEmpty) {
            if (from === null || to === null) {
                return true;
            }

            let fromNumber = parseFloat(from.replace(':', '.'));
            let toNumber = parseFloat(to.replace(':', '.'));

            if (noEmpty && fromNumber === toNumber) {
                return true;
            }

            return fromNumber > toNumber;
        },
    });
});
