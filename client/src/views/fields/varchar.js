/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('views/fields/varchar', 'views/fields/base', function (Dep) {

    return Dep.extend({

        type: 'varchar',

        listTemplate: 'fields/varchar/list',

        detailTemplate: 'fields/varchar/detail',

        searchTemplate: 'fields/varchar/search',

        searchTypeList: ['startsWith', 'contains', 'equals', 'endsWith', 'like', 'notContains', 'notEquals', 'notLike', 'isEmpty', 'isNotEmpty'],

        setup: function () {
            this.setupOptions();
            if (this.options.customOptionList) {
                this.setOptionList(this.options.customOptionList);
            }
        },

        setupOptions: function () {
        },

        setOptionList: function (optionList) {
            if (!this.originalOptionList) {
                this.originalOptionList = this.params.options || [];
            }
            this.params.options = Espo.Utils.clone(optionList);

            if (this.mode == 'edit') {
                if (this.isRendered()) {
                    this.reRender();
                }
            }
        },

        resetOptionList: function () {
            if (this.originalOptionList) {
                this.params.options = Espo.Utils.clone(this.originalOptionList);
            }

            if (this.mode == 'edit') {
                if (this.isRendered()) {
                    this.reRender();
                }
            }
        },

        setupSearch: function () {
            this.events = _.extend({
                'change select.search-type': function (e) {
                    var type = $(e.currentTarget).val();
                    this.handleSearchType(type);
                },
            }, this.events || {});
        },

        data: function () {
            var data = Dep.prototype.data.call(this);
            if (
                this.model.get(this.name) !== null
                &&
                this.model.get(this.name) !== ''
                &&
                this.model.has(this.name)
            ) {
                data.isNotEmpty = true;
            }
            data.valueIsSet = this.model.has(this.name);

            if (this.mode === 'search') {
                if (typeof this.searchParams.value === 'string') {
                    this.searchData.value = this.searchParams.value;
                }
            }
            return data;
        },

        handleSearchType: function (type) {
            if (~['isEmpty', 'isNotEmpty'].indexOf(type)) {
                this.$el.find('input.main-element').addClass('hidden');
            } else {
                this.$el.find('input.main-element').removeClass('hidden');
            }
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            if (this.mode == 'search') {
                var type = this.$el.find('select.search-type').val();
                this.handleSearchType(type);
            }

            if ((this.mode == 'edit'  || this.mode == 'search') && this.params.options && this.params.options.length) {
                this.$element.autocomplete({
                    minChars: 0,
                    lookup: this.params.options,
                    maxHeight: 200,
                    formatResult: function (suggestion) {
                        return this.getHelper().escapeString(suggestion.value);
                    }.bind(this),
                    lookupFilter: function (suggestion, query, queryLowerCase) {
                        if (suggestion.value.toLowerCase().indexOf(queryLowerCase) === 0) {
                            if (suggestion.value.length === queryLowerCase.length) return false;
                            return true;
                        }
                        return false;
                    },
                    onSelect: function () {
                        this.trigger('change');
                    }.bind(this)
                });
                this.$element.attr('autocomplete', 'espo-' + this.name);

                this.$element.on('focus', function () {
                    if (this.$element.val()) return;
                    this.$element.autocomplete('onValueChange');
                }.bind(this));
                this.once('render', function () {
                    this.$element.autocomplete('dispose');
                }, this);
                this.once('remove', function () {
                    this.$element.autocomplete('dispose');
                }, this);
            }
        },

        fetch: function () {
            var data = {};
            var value = this.$element.val();
            if (this.params.trim || this.forceTrim) {
                if (typeof value.trim === 'function') {
                    value = value.trim();
                }
            }
            data[this.name] = value || null;
            return data;
        },

        fetchSearch: function () {
            var type = this.fetchSearchType() || 'startsWith';

            var data;

            if (~['isEmpty', 'isNotEmpty'].indexOf(type)) {
                if (type == 'isEmpty') {
                    data = {
                        type: 'or',
                        value: [
                            {
                                type: 'isNull',
                                field: this.name,
                            },
                            {
                                type: 'equals',
                                field: this.name,
                                value: ''
                            }
                        ],
                        data: {
                            type: type
                        }
                    }
                } else {
                    data = {
                        type: 'and',
                        value: [
                            {
                                type: 'notEquals',
                                field: this.name,
                                value: ''
                            },
                            {
                                type: 'isNotNull',
                                field: this.name,
                                value: null
                            }
                        ],
                        data: {
                            type: type
                        }
                    }
                }
                return data;
            } else {
                var value = this.$element.val().toString().trim();
                value = value.trim();
                if (value) {
                    data = {
                        value: value,
                        type: type,
                        data: {
                            type: type
                        }
                    }
                    return data;
                }
            }
            return false;
        },

        getSearchType: function () {
            return this.getSearchParamsData().type || this.searchParams.typeFront || this.searchParams.type;
        }

    });
});
