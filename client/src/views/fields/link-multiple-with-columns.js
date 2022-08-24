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

define('views/fields/link-multiple-with-columns', ['views/fields/link-multiple', 'helpers/reg-exp-pattern'],
function (Dep, RegExpPattern) {

    /**
     * A link-multiple field with relation column(s).
     *
     * @class
     * @name Class
     * @extends module:views/fields/link-multiple.Class
     * @memberOf module:views/fields/link-multiple-with-columns
     */
    return Dep.extend(/** @lends module:views/fields/link-multiple-with-columns.Class# */{

        /**
         * @const
         */
        COLUMN_TYPE_VARCHAR: 'varchar',

        /**
         * @const
         */
        COLUMN_TYPE_ENUM: 'enum',

        /**
         * @const
         */
        COLUMN_TYPE_BOOL: 'bool',

        /**
         * @inheritDoc
         */
        setup: function () {
            Dep.prototype.setup.call(this);

            let columnsDefsInitial = this.columnsDefs || {};

            this.validations.push('columnPattern');

            /**
             * @type {Object.<string,*>}
             */
            this.columnsDefs = {};
            this.columnsName = this.name + 'Columns';
            this.columns = Espo.Utils.cloneDeep(this.model.get(this.columnsName) || {});

            this.listenTo(this.model, 'change:' + this.columnsName, () => {
                this.columns = Espo.Utils.cloneDeep(this.model.get(this.columnsName) || {});
            });

            let columns = this.getMetadata()
                .get(['entityDefs', this.model.name, 'fields', this.name, 'columns']) || {};

            /** @type {string[]} */
            this.columnList = this.columnList || Object.keys(columns);

            this.columnList.forEach(column => {
                if (column in columnsDefsInitial) {
                    this.columnsDefs[column] = Espo.Utils.cloneDeep(columnsDefsInitial[column]);

                    return;
                }

                if (column in columns) {
                    let field = columns[column];

                    let o = {};

                    o.field = field;
                    o.scope = this.foreignScope;

                    if (
                        !this.getMetadata().get(['entityDefs', this.foreignScope, 'fields', field, 'type']) &&
                        this.getMetadata().get(['entityDefs', this.model.name, 'fields', field, 'type'])
                    ) {
                        o.scope = this.model.name;
                    }

                    let fieldDefs = this.getMetadata().get(['entityDefs', o.scope, 'fields', field]) || {};

                    o.type = fieldDefs.type;

                    if (o.type === this.COLUMN_TYPE_ENUM || o.type === this.COLUMN_TYPE_VARCHAR) {
                        o.options = fieldDefs.options;
                    }

                    if ('default' in fieldDefs) {
                        o.default = fieldDefs.default;
                    }

                    if ('maxLength' in fieldDefs) {
                        o.maxLength = fieldDefs.maxLength;
                    }

                    if ('pattern' in fieldDefs) {
                        o.pattern = fieldDefs.pattern;
                    }

                    this.columnsDefs[column] = o;
                }
            });

            if (this.isEditMode() || this.isDetailMode()) {
                this.events['click a[data-action="toggleBoolColumn"]'] = (e) => {
                    let id = $(e.currentTarget).data('id');
                    let column = $(e.currentTarget).data('column');

                    this.toggleBoolColumn(id, column);
                };
            }

            this.on('render', this.disposeColumnAutocompletes, this);
            this.once('remove', this.disposeColumnAutocompletes, this);
        },

        toggleBoolColumn: function (id, column) {
            this.columns[id][column] = !this.columns[id][column];

            this.reRender();
        },

        /**
         * @inheritDoc
         */
        getAttributeList: function () {
            let list = Dep.prototype.getAttributeList.call(this);

            list.push(this.name + 'Columns');

            return list;
        },

        /**
         * Get an item HTML for detail mode.
         *
         * @param {string} id An ID.
         * @param {string} [name] An name.
         * @return {string}
         */
        getDetailLinkHtml: function (id, name) {
            // Do not use the `html` method to avoid XSS.

            name = name || this.nameHash[id] || id;

            let $el = $('<div>')
                .append(
                    $('<a>')
                        .attr('href', '#' + this.foreignScope + '/view/' + id)
                        .attr('data-id', id)
                        .text(name)
                );

            if (this.isDetailMode()) {
                let iconHtml = this.getIconHtml(id);

                if (iconHtml) {
                    $el.prepend(iconHtml);
                }
            }

            this.columnList.forEach(column => {
                let value = (this.columns[id] || {})[column] || '';
                let type = this.columnsDefs[column].type;
                let field = this.columnsDefs[column].field;
                let scope = this.columnsDefs[column].scope;

                if (value === '' || !value) {
                    return;
                }

                if (type !== this.COLUMN_TYPE_ENUM && type !== this.COLUMN_TYPE_VARCHAR) {
                    return;
                }

                let text = type === this.COLUMN_TYPE_ENUM ?
                    this.getLanguage().translateOption(value, field, scope) :
                    value;

                $el.append(
                    $('<span>').text(' '),
                    $('<span>').addClass('text-muted chevron-right'),
                    $('<span>').text(' '),
                    $('<span>').text(text).addClass('text-muted small')
                );
            });

            return $el.get(0).outerHTML;
        },

        /**
         * @inheritDoc
         */
        getValueForDisplay: function () {
            if (this.isDetailMode() || this.isListMode()) {
                let itemList = [];

                this.ids.forEach(id => {
                    itemList.push(
                        this.getDetailLinkHtml(id)
                    );
                });

                return itemList.join('');
            }
        },

        /**
         * @inheritDoc
         */
        deleteLink: function (id) {
            this.trigger('delete-link', id);
            this.trigger('delete-link:' + id);

            this.deleteLinkHtml(id);

            let index = this.ids.indexOf(id);

            if (index > -1) {
                this.ids.splice(index, 1);
            }

            delete this.nameHash[id];
            delete this.columns[id];

            this.afterDeleteLink(id);

            this.trigger('change');
        },

        /**
         * Get a column valus.
         * @param {string} id An ID.
         * @param {string} column A column.
         * @return {*}
         */
        getColumnValue: function (id, column) {
            return (this.columns[id] || {})[column];
        },

        addLink: function (id, name) {
            if (!~this.ids.indexOf(id)) {
                this.ids.push(id);
                this.nameHash[id] = name;
                this.columns[id] = {};

                this.columnList.forEach(column => {
                    this.columns[id][column] = null;

                    if ('default' in this.columnsDefs[column]) {
                        this.columns[id][column] = this.columnsDefs[column].default;
                    }
                });

                this.addLinkHtml(id, name);

                this.afterAddLink(id);

                this.trigger('add-link', id);
                this.trigger('add-link:' + id);
            }

            this.trigger('change');
        },

        /**
         * @inheritDoc
         */
        afterRender: function () {
            Dep.prototype.afterRender.call(this);
        },

        /**
         * @inheritDoc
         */
        afterAddLink: function (id) {
            Dep.prototype.afterAddLink.call(this, id);
        },

        /**
         * @param {string} column
         * @param {string} id
         * @param {*} value
         * @return {JQuery}
         */
        getJQSelect: function (column, id, value) {
            // Do not use the `html` method to avoid XSS.

            let field = this.columnsDefs[column].field;
            let scope = this.columnsDefs[column].scope;
            let options = this.columnsDefs[column].options || [];

            let $select = $('<select>')
                .addClass('role form-control input-sm pull-right')
                .attr('data-id', id)
                .attr('data-column', column);

            options.forEach(itemValue => {
                let text = this.getLanguage().translateOption(itemValue, field, scope);

                let $option = $('<option>')
                    .val(itemValue)
                    .text(text);

                if (itemValue === (value || '')) {
                    $option.attr('selected', 'selected');
                }

                $select.append($option);
            })

            return $select;
        },

        /**
         * @param {string} column
         * @param {string} id
         * @param {*} value
         * @return {JQuery}
         */
        getJQInput: function (column, id, value) {
            // Do not use the `html` method to avoid XSS.

            let field = this.columnsDefs[column].field;
            let scope = this.columnsDefs[column].scope;
            let maxLength = this.columnsDefs[column].maxLength;

            let text = this.translate(field, 'fields', scope);

            let $input = $('<input>')
                .addClass('role form-control input-sm pull-right')
                .attr('data-column', column)
                .attr('placeholder', text)
                .attr('data-id', id)
                .attr('value', value || '');

            if (maxLength) {
                $input.attr('maxlength', maxLength);
            }

            return $input;
        },

        /**
         * @param {string} column
         * @param {string} id
         * @param {*} value
         * @return {JQuery}
         */
        getJQLi: function (column, id, value) {
            // Do not use the `html` method to avoid XSS.

            let field = this.columnsDefs[column].field;
            let scope = this.columnsDefs[column].scope;

            let text = this.translate(field, 'fields', scope);

            return $('<li>')
                .append(
                    $('<a>')
                        .attr('role', 'button')
                        .attr('tabindex', '0')
                        .attr('data-action', 'toggleBoolColumn')
                        .attr('data-column', column)
                        .attr('data-id', id)
                        .append(
                            $('<span>')
                                .addClass('check-icon fas fa-check pull-right')
                                .addClass(!value ? 'hidden' : '')
                        )
                        .append(
                            $('<div>').text(text)
                        )
                );
        },

        /**
         * @inheritDoc
         */
        addLinkHtml: function (id, name) {
            if (this.isSearchMode()) {
                return Dep.prototype.addLinkHtml.call(this, id, name);
            }

            // Do not use the `html` method to avoid XSS.

            let $container = this.$el.find('.link-container');

            let $el = $('<div>')
                .addClass('form-inline clearfix')
                .addClass('list-group-item link-with-role link-group-item-with-columns')
                .addClass('link-' + id);

            let $remove = $('<a>')
                .attr('role', 'button')
                .attr('tabindex', '0')
                .attr('data-id', id)
                .attr('data-action', 'clearLink')
                .addClass('pull-right')
                .append(
                    $('<span>').addClass('fas fa-times')
                );

            let $name = $('<div>')
                .addClass('link-item-name')
                .text(name)
                .append('&nbsp;')

            let $columnList = [];
            let $liList = [];

            this.columnList.forEach(column => {
                let value = (this.columns[id] || {})[column];

                let type = this.columnsDefs[column].type;

                if (type === this.COLUMN_TYPE_ENUM) {
                    $columnList.push(
                        this.getJQSelect(column, id, value)
                    );

                    return;
                }

                if (type === this.COLUMN_TYPE_VARCHAR) {
                    $columnList.push(
                        this.getJQInput(column, id, value)
                    );

                    return;
                }

                if (type === this.COLUMN_TYPE_BOOL) {
                    $liList.push(
                        this.getJQLi(column, id, value)
                    );
                }
            });

            let $left = $('<div>');
            let $right = $('<div>');

            $columnList.forEach($item => $left.append($item));

            if ($liList.length) {
                let $ul = $('<ul>').addClass('dropdown-menu');

                $liList.forEach($item => $ul.append($item));

                $left.append(
                    $('<div>')
                        .addClass('btn-group pull-right')
                        .append(
                            $('<button>')
                                .attr('type', 'button')
                                .attr('data-toggle', 'dropdown')
                                .addClass('btn btn-link btn-sm dropdown-toggle')
                                .append(
                                    $('<span>').addClass('caret')
                                )
                        )
                        .append($ul)
                );
            }

            $left.append($name);
            $right.append($remove);

            $el.append($left);
            $el.append($right);

            $container.append($el);

            if (this.isEditMode()) {
                $columnList.forEach($column => {
                    let fetch = ($target) => {
                        if (!$target || !$target.length) {
                            return;
                        }

                        let column = $target.data('column');
                        let value = $target.val().toString().trim();
                        let id = $target.data('id');

                        if (value === '') {
                            value = null;
                        }

                        this.columns[id] = this.columns[id] || {};
                        this.columns[id][column] = value;
                    };

                    $column.on('change', e => {
                        let $target = $(e.currentTarget);

                        fetch($target);
                        this.trigger('change');
                    });

                    fetch($column);
                });

                this.initAutocomplete(id);
            }

            return $el;
        },

        initAutocomplete: function (id) {
            if (!this._autocompleteElementList) {
                this._autocompleteElementList = [];
            }

            this.columnList.forEach(column => {
                let type = this.columnsDefs[column].type;

                if (type === this.COLUMN_TYPE_VARCHAR) {
                    let options = this.columnsDefs[column].options;

                    if (options && options.length) {
                        let $element = this.$el.find('[data-column="'+column+'"][data-id="'+id+'"]');

                        if (!$element.length) {
                            return;
                        }

                        $element.autocomplete({
                            minChars: 0,
                            lookup: options,
                            maxHeight: 200,
                            beforeRender: (c) => {
                                c.addClass('small');
                            },
                            formatResult: (suggestion) => {
                                return this.getHelper().escapeString(suggestion.value);
                            },
                            lookupFilter: (suggestion, query, queryLowerCase) => {
                                if (suggestion.value.toLowerCase().indexOf(queryLowerCase) === 0) {
                                    if (suggestion.value.length === queryLowerCase.length) {
                                        return false;
                                    }

                                    return true;
                                }

                                return false;
                            },
                            onSelect: () => {
                                this.trigger('change');
                                $element.trigger('change');
                            },
                        });

                        $element.attr('autocomplete', 'espo-' + this.name + '-' + column + '-' + id);

                        $element.on('focus', () => {
                            if ($element.val()) {
                                return;
                            }

                            $element.autocomplete('onValueChange');
                        });

                        this._autocompleteElementList.push($element);

                        this.once('delete-link:' + id, () => {
                            $element.autocomplete('dispose');
                        });
                    }
                }
            });
        },

        disposeColumnAutocompletes: function () {
            if (this._autocompleteElementList && this._autocompleteElementList.length) {
                this._autocompleteElementList.forEach($el =>{
                    $el.autocomplete('dispose');
                });

                this._autocompleteElementList = [];
            }
        },

        validateColumnPattern: function () {
            let result = false;

            let columnList = this.columnList
                .filter(column => this.columnsDefs[column].type === this.COLUMN_TYPE_VARCHAR)
                .filter(column => this.columnsDefs[column].pattern);

            for (let column of columnList) {
                for (let id of this.ids) {
                    let value = this.getColumnValue(id, column);

                    if (!value) {
                        continue;
                    }

                    if (this.validateColumnPatternValue(id, column, value)) {
                        result = true;
                    }
                }
            }

            return result;
        },

        validateColumnPatternValue: function (id, column, value) {
            let pattern = this.columnsDefs[column].pattern;
            let field = this.columnsDefs[column].field;
            let scope = this.columnsDefs[column].scope;

            /** @type module:helpers/reg-exp-pattern.Class */
            let helper = new RegExpPattern(this.getMetadata(), this.getLanguage());

            let result = helper.validate(pattern, value, field, scope);

            if (!result) {
                return false;
            }

            this.showValidationMessage(result.message, '[data-column="' + column + '"][data-id="' + id + '"]');

            return true;
        },

        /**
         * @inheritDoc
         */
        fetch: function () {
            let data = Dep.prototype.fetch.call(this);

            data[this.columnsName] = Espo.Utils.cloneDeep(this.columns);

            return data;
        },
    });
});
