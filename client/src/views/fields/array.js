/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/fields/array', ['views/fields/base', 'lib!Selectize', 'lib!underscore'],
function (Dep, Selectize, _) {

    /**
     * An array field.
     *
     * @class
     * @name Class
     * @extends module:views/fields/base.Class
     * @memberOf module:views/fields/array
     */
    return Dep.extend(/** @lends module:views/fields/array.Class# */{

        type: 'array',

        listTemplate: 'fields/array/list',

        detailTemplate: 'fields/array/detail',

        editTemplate: 'fields/array/edit',

        searchTemplate: 'fields/array/search',

        searchTypeList: ['anyOf', 'noneOf', 'allOf', 'isEmpty', 'isNotEmpty'],

        maxItemLength: null,

        validations: ['required', 'maxCount'],

        addItemModalView: 'views/modals/array-field-add',

        itemDelimiter: ':,:',

        /**
         * @inheritDoc
         */
        data: function () {
            var itemHtmlList = [];

            (this.selected || []).forEach((value) => {
                itemHtmlList.push(this.getItemHtml(value));
            });

            return _.extend({
                selected: this.selected,
                translatedOptions: this.translatedOptions,
                hasOptions: this.params.options ? true : false,
                itemHtmlList: itemHtmlList,
                isEmpty: (this.selected || []).length === 0,
                valueIsSet: this.model.has(this.name),
                maxItemLength: this.maxItemLength,
                allowCustomOptions: this.allowCustomOptions,
            }, Dep.prototype.data.call(this));
        },

        /**
         * @inheritDoc
         */
        events: {
            'click [data-action="removeValue"]': function (e) {
                var value = $(e.currentTarget).attr('data-value').toString();

                this.removeValue(value);
            },
            'click [data-action="showAddModal"]': function () {
                this.actionAddItem();
            },
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.noEmptyString = this.params.noEmptyString;

            this.listenTo(this.model, 'change:' + this.name, () => {
                this.selected = Espo.Utils.clone(this.model.get(this.name)) || [];
            });

            this.selected = Espo.Utils.clone(this.model.get(this.name) || []);

            if (Object.prototype.toString.call(this.selected) !== '[object Array]')    {
                this.selected = [];
            }

            this.styleMap = this.params.style || {};

            this.setupOptions();

            if ('translatedOptions' in this.options) {
                this.translatedOptions = this.options.translatedOptions;
            }
            if ('translatedOptions' in this.params) {
                this.translatedOptions = this.params.translatedOptions;
            }

            if (!this.translatedOptions) {
                this.setupTranslation();
            }

            this.displayAsLabel = this.params.displayAsLabel || this.displayAsLabel;
            this.displayAsList = this.params.displayAsList || this.displayAsList;

            if (this.params.isSorted && this.translatedOptions) {
                this.params.options = Espo.Utils.clone(this.params.options);
                this.params.options = this.params.options.sort((v1, v2) => {
                     return (this.translatedOptions[v1] || v1).localeCompare(this.translatedOptions[v2] || v2);
                });
            }

            if (this.options.customOptionList) {
                this.setOptionList(this.options.customOptionList, true);
            }

            if (this.params.allowCustomOptions || !this.params.options) {
                this.allowCustomOptions = true;
            }
        },

        setupSearch: function () {
            this.events = _.extend({
                'change select.search-type': (e) => {
                    this.handleSearchType($(e.currentTarget).val());
                }
            }, this.events || {});
        },

        handleSearchType: function (type) {
            var $inputContainer = this.$el.find('div.input-container');

            if (~['anyOf', 'noneOf', 'allOf'].indexOf(type)) {
                $inputContainer.removeClass('hidden');
            } else {
                $inputContainer.addClass('hidden');
            }
        },

        setupTranslation: function () {
            var t = {};

            if (this.params.translation) {
                var arr = this.params.translation.split('.');

                var pointer = this.getLanguage().data;

                arr.forEach((key) => {
                    if (key in pointer) {
                        pointer = pointer[key];

                        t = pointer;
                    }
                });
            }
            else {
                t = this.translate(this.name, 'options', this.model.name);
            }

            this.translatedOptions = null;

            var translatedOptions = {};

            if (this.params.options) {
                this.params.options.forEach((o) => {
                    if (typeof t === 'object' && o in t) {
                        translatedOptions[o] = t[o];
                    } else {
                        translatedOptions[o] = o;
                    }
                });

                this.translatedOptions = translatedOptions;
            }
        },

        setupOptions: function () {},

        setOptionList: function (optionList, silent) {
            let previousOptions = this.params.options;

            if (!this.originalOptionList) {
                this.originalOptionList = this.params.options;
            }

            this.params.options = Espo.Utils.clone(optionList);

            let isChanged = !_(previousOptions).isEqual(optionList);

            if (this.mode === 'edit' && !silent && isChanged) {
                var selectedOptionList = [];

                this.selected.forEach(option => {
                    if (~optionList.indexOf(option)) {
                        selectedOptionList.push(option);
                    }
                });

                this.selected = selectedOptionList;

                if (this.isRendered()) {
                    this.reRender();

                    this.trigger('change');
                }
                else {
                    this.once('after:render', () => {
                        this.trigger('change');
                    });
                }
            }
        },

        setTranslatedOptions: function (translatedOptions) {
            this.translatedOptions = translatedOptions;
        },

        resetOptionList: function () {
            if (this.originalOptionList) {
                this.params.options = Espo.Utils.clone(this.originalOptionList);
            }

            if (this.mode === 'edit') {
                if (this.isRendered()) {
                    this.reRender();
                }
            }
        },

        controlAddItemButton: function () {
            var $select = this.$select;

            if (!$select) {
                return;
            }

            if (!$select.get(0)) {
                return;
            }

            var value = $select.val().toString();

            if (!value && this.params.noEmptyString) {
                this.$addButton.addClass('disabled').attr('disabled', 'disabled');
            }
            else {
                this.$addButton.removeClass('disabled').removeAttr('disabled');
            }
        },

        afterRender: function () {
            if (this.mode === 'edit') {
                this.$list = this.$el.find('.list-group');

                var $select = this.$select = this.$el.find('.select');

                if (this.allowCustomOptions) {
                    this.$addButton = this.$el.find('button[data-action="addItem"]');

                    this.$addButton.on('click', () => {
                        var value = this.$select.val().toString();

                        this.addValue(value);

                        $select.val('');

                        this.controlAddItemButton();
                    });

                    $select.on('input', () => {
                        this.controlAddItemButton();
                    });

                    $select.on('keypress', (e) => {
                        if (e.keyCode === 13) {
                            var value = $select.val().toString();

                            if (this.noEmptyString) {
                                if (value === '') {
                                    return;
                                }
                            }

                            this.addValue(value);

                            $select.val('');

                            this.controlAddItemButton();
                        }
                    });

                    this.controlAddItemButton();
                }

                this.$list.sortable({
                    stop: () => {
                        this.fetchFromDom();
                        this.trigger('change');
                    }
                });
            }

            if (this.mode === 'search') {
                this.renderSearch();
            }
        },

        renderSearch: function () {
            this.$element = this.$el.find('.main-element');

            var valueList = this.getSearchParamsData().valueList || this.searchParams.valueFront || [];

            this.$element.val(valueList.join(this.itemDelimiter));

            var data = [];

            (this.params.options || []).forEach((value) => {
                var label = this.getLanguage().translateOption(value, this.name, this.scope);

                if (this.translatedOptions) {
                    if (value in this.translatedOptions) {
                        label = this.translatedOptions[value];
                    }
                }

                if (label === '') {
                    return;
                };

                data.push({
                    value: value,
                    label: label,
                });
            });

            var selectizeOptions = {
                options: data,
                delimiter: this.itemDelimiter,
                labelField: 'label',
                valueField: 'value',
                highlight: false,
                searchField: ['label'],
                plugins: ['remove_button'],
            };

            if (!this.matchAnyWord) {
                selectizeOptions.score = function (search) {
                    var score = this.getScoreFunction(search);

                    search = search.toLowerCase();

                    return function (item) {
                        if (item.label.toLowerCase().indexOf(search) === 0) {
                            return score(item);
                        }

                        return 0;
                    };
                };
            }

            if (this.allowCustomOptions) {
                selectizeOptions.persist = false;

                selectizeOptions.create = function (input) {
                    return {
                        value: input,
                        label: input,
                    };
                };

                selectizeOptions.render = {
                    option_create: function (data, escape) {
                        return '<div class="create"><strong>' + escape(data.input) + '</strong>&hellip;</div>';
                    },
                };
            }

            this.$element.selectize(selectizeOptions);

            this.$el.find('.selectize-dropdown-content').addClass('small');

            var type = this.$el.find('select.search-type').val();

            this.handleSearchType(type);

            this.$el.find('select.search-type').on('change', () => {
                this.trigger('change');
            });

            this.$element.on('change', () => {
                this.trigger('change');
            });
        },

        fetchFromDom: function () {
            var selected = [];

            this.$el.find('.list-group .list-group-item').each((i, el) => {
                var value = $(el).attr('data-value').toString();

                selected.push(value);
            });

            this.selected = selected;
        },

        getValueForDisplay: function () {
            var list = this.selected.map(item => {
                var label = null;

                if (this.translatedOptions !== null) {
                    if (item in this.translatedOptions) {
                        label = this.translatedOptions[item];

                        label = this.escapeValue(label);
                    }
                }

                if (label === null) {
                    label = this.escapeValue(item);
                }

                if (label === '') {
                    label = this.translate('None');
                }

                var style = this.styleMap[item] || 'default';

                if (this.params.displayAsLabel) {
                    label = '<span class="label label-md label-'+style+'">' + label + '</span>';
                }
                else {
                    if (style && style !== 'default') {
                        label = '<span class="text-'+style+'">' + label + '</span>';
                    }
                }

                return label;
            });

            if (this.displayAsList) {
                if (!list.length) {
                    return '';
                }

                var itemClassName = 'multi-enum-item-container';

                if (this.displayAsLabel) {
                    itemClassName += ' multi-enum-item-label-container';
                }

                return '<div class="'+itemClassName+'">' +
                    list.join('</div><div class="'+itemClassName+'">') + '</div>';
            }
            else if (this.displayAsLabel) {
                return list.join(' ');
            }
            else {
                return list.join(', ');
            }
        },

        getItemHtml: function (value) {
            if (this.translatedOptions !== null) {
                for (var item in this.translatedOptions) {
                    if (this.translatedOptions[item] === value) {
                        value = item;

                        break;
                    }
                }
            }

            value = value.toString();

            var valueSanitized = this.escapeValue(value);

            var label = valueSanitized;

            if (this.translatedOptions) {
                if ((value in this.translatedOptions)) {
                    label = this.translatedOptions[value];
                    label = label.toString();
                    label = this.escapeValue(label);
                }
            }
            var html =
                '<div class="list-group-item" data-value="' + valueSanitized + '" style="cursor: default;">' +
                label +
                '&nbsp;<a href="javascript:" class="pull-right" ' +
                'data-value="' + valueSanitized + '" data-action="removeValue"><span class="fas fa-times"></a>' +
                '</div>';

            return html;
        },

        escapeValue: function (value) {
            return Handlebars.Utils.escapeExpression(value);
        },

        addValue: function (value) {
            if (this.selected.indexOf(value) === -1) {
                var html = this.getItemHtml(value);

                this.$list.append(html);

                this.selected.push(value);

                this.trigger('change');
            }
        },

        removeValue: function (value) {
            var valueInternal = value.replace(/"/g, '\\"');

            this.$list.children('[data-value="' + valueInternal + '"]').remove();

            var index = this.selected.indexOf(value);

            this.selected.splice(index, 1);

            this.trigger('change');
        },

        fetch: function () {
            var data = {};

            var list = Espo.Utils.clone(this.selected || []);

            if (this.params.isSorted && this.translatedOptions) {
                list = list.sort((v1, v2) => {
                     return (this.translatedOptions[v1] || v1)
                         .localeCompare(this.translatedOptions[v2] || v2);
                });
            }

            data[this.name] = list;

            return data;
        },

        fetchSearch: function () {
            var type = this.$el.find('select.search-type').val() || 'anyOf';

            if (~['anyOf', 'noneOf', 'allOf'].indexOf(type)) {
                var valueList = this.$element.val().split(this.itemDelimiter);

                if (valueList.length === 1 && valueList[0] === '') {
                    valueList = [];
                }

                if (valueList.length === 0) {
                   if (type === 'anyOf') {
                       return {
                           type: 'any',
                           data: {
                               type: type,
                               valueList: valueList,
                           },
                       };
                   }

                   if (type === 'noneOf') {
                       return {
                           type: 'any',
                           data: {
                               type: type,
                               valueList: valueList,
                           },
                       };
                   }

                   if (type === 'allOf') {
                       return {
                           type: 'any',
                           data: {
                               type: type,
                               valueList: valueList,
                           },
                       };
                   }
               }
            }

            if (type === 'anyOf') {
                var data = {
                    type: 'arrayAnyOf',
                    value: valueList,
                    data: {
                        type: 'anyOf',
                        valueList: valueList
                    }
                };
                if (!valueList.length) {
                    data.value = null;
                }
                return data;
            }

            if (type === 'noneOf') {
                var data = {
                    type: 'arrayNoneOf',
                    value: valueList,
                    data: {
                        type: 'noneOf',
                        valueList: valueList
                    }
                };

                return data;
            }

            if (type === 'allOf') {
                var data = {
                    type: 'arrayAllOf',
                    value: valueList,
                    data: {
                        type: 'allOf',
                        valueList: valueList
                    }
                };

                if (!valueList.length) {
                    data.value = null;
                }

                return data;
            }

            if (type === 'isEmpty') {
                var data = {
                    type: 'arrayIsEmpty',
                    data: {
                        type: 'isEmpty'
                    }
                };

                return data;
            }

            if (type === 'isNotEmpty') {
                var data = {
                    type: 'arrayIsNotEmpty',
                    data: {
                        type: 'isNotEmpty'
                    }
                };

                return data;
            }
        },

        validateRequired: function () {
            if (this.isRequired()) {
                var value = this.model.get(this.name);

                if (!value || value.length === 0) {
                    var msg = this.translate('fieldIsRequired', 'messages')
                        .replace('{field}', this.getLabelText());

                    this.showValidationMessage(msg, '.array-control-container');

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

                    this.showValidationMessage(msg, '.array-control-container');

                    return true;
                }
            }
        },

        getSearchType: function () {
            return this.getSearchParamsData().type || 'anyOf';
        },

        getAddItemModalOptions: function () {
            var options = [];

            this.params.options.forEach((item) => {
                if (!~this.selected.indexOf(item)) {
                    options.push(item);
                }
            });

            return {
                options: options,
                translatedOptions: this.translatedOptions,
            };
        },

        actionAddItem: function () {
            this.createView('addModal', this.addItemModalView, this.getAddItemModalOptions(), (view) => {
                view.render();

                view.once('add', (item) => {
                    this.addValue(item);

                    view.close();
                });

                view.once('add-mass', (items) => {
                    items.forEach((item) => {
                        this.addValue(item);
                    });

                    view.close();
                });
            });
        },
    });
});
