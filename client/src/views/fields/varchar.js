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

define('views/fields/varchar', ['views/fields/base', 'helpers/reg-exp-pattern'], function (Dep, RegExpPattern) {

    /**
     * A varchar field.
     *
     * @class
     * @name Class
     * @extends module:views/fields/base.Class
     * @memberOf module:views/fields/varchar
     */
    return Dep.extend(/** @lends module:views/fields/varchar.Class# */{

        type: 'varchar',

        listTemplate: 'fields/varchar/list',

        detailTemplate: 'fields/varchar/detail',

        searchTemplate: 'fields/varchar/search',

        searchTypeList: [
            'startsWith',
            'contains',
            'equals',
            'endsWith',
            'like',
            'notContains',
            'notEquals',
            'notLike',
            'isEmpty',
            'isNotEmpty',
        ],

        /**
         * @inheritDoc
         */
        validations: [
            'required',
            'pattern',
        ],

        /**
         * Use an autocomplete requesting data from the backend.
         *
         * @protected
         * @type {boolean}
         */
        useAutocompleteUrl: false,

        /**
         * No spell-check.
         *
         * @protected
         * @type {boolean}
         */
        noSpellCheck: false,

        setup: function () {
            this.setupOptions();

            this.noSpellCheck = this.noSpellCheck || this.params.noSpellCheck;

            if (this.options.customOptionList) {
                this.setOptionList(this.options.customOptionList);
            }
        },

        /**
         * Set up options.
         */
        setupOptions: function () {},

        /**
         * Set options.
         *
         * @param {string[]} optionList Options.
         */
        setOptionList: function (optionList) {
            if (!this.originalOptionList) {
                this.originalOptionList = this.params.options || [];
            }

            this.params.options = Espo.Utils.clone(optionList);

            if (this.isEditMode()) {
                if (this.isRendered()) {
                    this.reRender();
                }
            }
        },

        /**
         * Reset options.
         */
        resetOptionList: function () {
            if (this.originalOptionList) {
                this.params.options = Espo.Utils.clone(this.originalOptionList);
            }

            if (this.isEditMode()) {
                if (this.isRendered()) {
                    this.reRender();
                }
            }
        },

        /**
         * Compose an autocomplete URL.
         *
         * @param {string} q A query.
         * @return {string}
         */
        getAutocompleteUrl: function (q) {
            return '';
        },

        transformAutocompleteResult: function (response) {
            let responseParsed = JSON.parse(response);

            let list = [];

            responseParsed.list.forEach(item => {
                list.push({
                    id: item.id,
                    name: item.name || item.id,
                    data: item.id,
                    value: item.name || item.id,
                    attributes: item,
                });
            });

            return {
                suggestions: list,
            };
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
            let data = Dep.prototype.data.call(this);

            if (
                this.model.get(this.name) !== null &&
                this.model.get(this.name) !== '' &&
                this.model.has(this.name)
            ) {
                data.isNotEmpty = true;
            }

            data.valueIsSet = this.model.has(this.name);

            if (this.isSearchMode()) {
                if (typeof this.searchParams.value === 'string') {
                    this.searchData.value = this.searchParams.value;
                }
            }

            data.noSpellCheck = this.noSpellCheck;

            return data;
        },

        handleSearchType: function (type) {
            if (~['isEmpty', 'isNotEmpty'].indexOf(type)) {
                this.$el.find('input.main-element').addClass('hidden');

                return;
            }

            this.$el.find('input.main-element').removeClass('hidden');
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.isSearchMode()) {
                let type = this.$el.find('select.search-type').val();

                this.handleSearchType(type);
            }

            if (
                (this.isEditMode() || this.isSearchMode()) &&
                (
                    this.params.options && this.params.options.length ||
                    this.useAutocompleteUrl
                )
            ) {
                let autocompleteOptions = {
                    minChars: 0,
                    lookup: this.params.options,
                    maxHeight: 200,
                    beforeRender: ($c) => {
                        if (this.$element.hasClass('input-sm')) {
                            $c.addClass('small');
                        }
                    },
                    formatResult: (suggestion) => {
                        return this.getHelper().escapeString(suggestion.value);
                    },
                    lookupFilter: (suggestion, query, queryLowerCase) => {
                        if (suggestion.value.toLowerCase().indexOf(queryLowerCase) === 0) {
                            return suggestion.value.length !== queryLowerCase.length;
                        }

                        return false;
                    },
                    onSelect: () => {
                        this.trigger('change');
                    },
                };

                if (this.useAutocompleteUrl) {
                    autocompleteOptions.serviceUrl = q => this.getAutocompleteUrl(q);
                    autocompleteOptions.transformResult = response =>
                        this.transformAutocompleteResult(response);
                    autocompleteOptions.noCache = true;
                    autocompleteOptions.lookup = null;
                }

                this.$element.autocomplete(autocompleteOptions);
                this.$element.attr('autocomplete', 'espo-' + this.name);

                this.$element.on('focus', () => {
                    if (this.$element.val()) {
                        return;
                    }

                    this.$element.autocomplete('onValueChange');
                });

                this.once('render', () => this.$element.autocomplete('dispose'));
                this.once('remove', () => this.$element.autocomplete('dispose'));
            }

            if (this.isSearchMode()) {
                this.$el.find('select.search-type').on('change', () => {
                    this.trigger('change');
                });

                this.$element.on('input', () => {
                    this.trigger('change');
                });
            }
        },

        validatePattern: function () {
            let pattern = this.params.pattern;

            return this.fieldValidatePattern(this.name, pattern);
        },

        /**
         * Used by other field views.
         *
         * @param {string} name
         * @param {string} [pattern]
         */
        fieldValidatePattern: function (name, pattern) {
            pattern = pattern || this.model.getFieldParam(name, 'pattern');
            /** @var {string|null} value */
            let value = this.model.get(name);

            if (!pattern) {
                return false;
            }

            /** @type module:helpers/reg-exp-pattern.Class */
            let helper = new RegExpPattern(this.getMetadata(), this.getLanguage());

            let result = helper.validate(pattern, value, name, this.entityType);

            if (!result) {
                return false;
            }

            this.showValidationMessage(result.message, '[data-name="' + name + '"]');

            return true;
        },

        fetch: function () {
            let data = {};

            let value = this.$element.val().trim();

            data[this.name] = value || null;

            return data;
        },

        fetchSearch: function () {
            let type = this.fetchSearchType() || 'startsWith';

            if (~['isEmpty', 'isNotEmpty'].indexOf(type)) {
                if (type === 'isEmpty') {
                    return {
                        type: 'or',
                        value: [
                            {
                                type: 'isNull',
                                field: this.name,
                            },
                            {
                                type: 'equals',
                                field: this.name,
                                value: '',
                            }
                        ],
                        data: {
                            type: type,
                        },
                    };
                }

                return {
                    type: 'and',
                    value: [
                        {
                            type: 'notEquals',
                            field: this.name,
                            value: '',
                        },
                        {
                            type: 'isNotNull',
                            field: this.name,
                            value: null,
                        }
                    ],
                    data: {
                        type: type,
                    },
                };
            }

            let value = this.$element.val().toString().trim();

            if (!value) {
                // @todo Change to `null` in v7.4 (and for all other fields).
                return false;
            }

            return {
                value: value,
                type: type,
                data: {
                    type: type,
                },
            };
        },

        getSearchType: function () {
            return this.getSearchParamsData().type || this.searchParams.typeFront ||
                this.searchParams.type;
        },
    });
});
