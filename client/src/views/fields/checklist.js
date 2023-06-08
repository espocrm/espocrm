/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

/** @module views/fields/checklist */

import Dep from 'views/fields/array';

/**
 * @class Class
 * @extends module:views/fields/base
 */
export default Dep.extend(/** @lends Class# */{

    type: 'checklist',

    listTemplate: 'fields/array/list',
    detailTemplate: 'fields/checklist/detail',
    editTemplate: 'fields/checklist/edit',

    isInversed: false,

    events: {},

    data: function () {
        return {
            optionDataList: this.getOptionDataList(),
            ...Dep.prototype.data.call(this),
        };
    },

    setup: function () {
        Dep.prototype.setup.call(this);

        this.params.options = this.params.options || [];

        this.isInversed = this.params.isInversed || this.options.isInversed || this.isInversed;
    },

    afterRender: function () {
        if (this.isSearchMode()) {
            this.renderSearch();
        }

        if (this.isEditMode()) {
            this.$el.find('input').on('change', () => {
                this.trigger('change');
            });
        }
    },

    getOptionDataList: function () {
        let valueList = this.model.get(this.name) || [];
        let list = [];

        this.params.options.forEach((item) => {
            let isChecked = ~valueList.indexOf(item);
            let dataName = item;
            let id = this.cid + '-' + Espo.Utils.camelCaseToHyphen(item.replace(/\s+/g, '-'));

            if (this.isInversed) {
                isChecked = !isChecked;
            }

            list.push({
                name: item,
                isChecked: isChecked,
                dataName: dataName,
                id: id,
                label: this.translatedOptions[item] || item,
            });
        });

        return list;
    },

    fetch: function () {
        let list = [];

        this.params.options.forEach(item => {
            let $item = this.$el.find('input[data-name="' + item + '"]');
            let isChecked = $item.get(0) && $item.get(0).checked;

            if (this.isInversed) {
                isChecked = !isChecked;
            }

            if (isChecked) {
                list.push(item);
            }
        });

        let data = {};

        data[this.name] = list;

        return data;
    },

    validateRequired: function () {
        if (!this.isRequired()) {
            return;
        }

        let value = this.model.get(this.name);

        if (!value || value.length === 0) {
            var msg = this.translate('fieldIsRequired', 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg, '.checklist-item-container:last-child input');

            return true;
        }
    },

    validateMaxCount: function () {
        if (!this.params.maxCount) {
            return;
        }

        let itemList = this.model.get(this.name) || [];

        if (itemList.length > this.params.maxCount) {
            let msg =
                this.translate('fieldExceedsMaxCount', 'messages')
                    .replace('{field}', this.getLabelText())
                    .replace('{maxCount}', this.params.maxCount.toString());

            this.showValidationMessage(msg, '.checklist-item-container:last-child input');

            return true;
        }
    },
});
