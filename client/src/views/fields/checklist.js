/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/fields/checklist', ['views/fields/array'], function (Dep) {

    return Dep.extend({

        type: 'checklist',

        listTemplate: 'fields/array/list',

        detailTemplate: 'fields/checklist/detail',

        editTemplate: 'fields/checklist/edit',

        isInversed: false,

        events: {
        },

        data: function () {
            return _.extend({
                optionDataList: this.getOptionDataList(),
            }, Dep.prototype.data.call(this));
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.params.options = this.params.options || [];

            this.isInversed = this.params.isInversed || this.options.isInversed || this.isInversed;
        },

        afterRender: function () {
            if (this.mode == 'search') {
                this.renderSearch();
            }

            if (this.isEditMode()) {
                this.$el.find('input').on('change', function () {
                    this.trigger('change');
                }.bind(this));
            }
        },

        getOptionDataList: function () {
            var valueList = this.model.get(this.name) || [];
            var list = [];

            this.params.options.forEach(function (item) {
                var isChecked = ~valueList.indexOf(item);
                var dataName = 'checklistItem-' + this.name + '-' + item;
                var id = 'checklist-item-' + this.name + '-' + item;

                if (this.isInversed) isChecked = !isChecked;
                list.push({
                    name: item,
                    isChecked: isChecked,
                    dataName: dataName,
                    id: id,
                    label: this.translatedOptions[item] || item,
                });
            }, this);

            return list;
        },

        fetch: function () {
            var list = [];

            this.params.options.forEach(function (item) {
                var $item = this.$el.find('input[data-name="checklistItem-' + this.name + '-' + item + '"]');
                var isChecked = $item.get(0) && $item.get(0).checked;
                if (this.isInversed)
                    isChecked = !isChecked;
                if (isChecked)
                    list.push(item);
            }, this);

            var data = {};
            data[this.name] = list;

            return data;
        },

        validateRequired: function () {
            if (this.isRequired()) {
                var value = this.model.get(this.name);
                if (!value || value.length == 0) {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.getLabelText());
                    this.showValidationMessage(msg, '.checklist-item-container:last-child input');
                    return true;
                }
            }
        },

        validateMaxCount: function () {
            if (this.params.maxCount) {
                var itemList = this.model.get(this.name) || [];
                if (itemList.length > this.params.maxCount) {
                    var msg =
                        this.translate('fieldExceedsMaxCount', 'messages')
                            .replace('{field}', this.getLabelText())
                            .replace('{maxCount}', this.params.maxCount.toString());
                    this.showValidationMessage(msg, '.checklist-item-container:last-child input');
                    return true;
                }
            }
        },
    });
});
