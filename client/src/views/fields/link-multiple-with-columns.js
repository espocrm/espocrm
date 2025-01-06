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

/** @module views/fields/link-multiple-with-columns */

import LinkMultipleFieldView from 'views/fields/link-multiple';
import RegExpPattern from 'helpers/reg-exp-pattern';
import Select from 'ui/select';
import Autocomplete from 'ui/autocomplete';

/**
 * A link-multiple field with relation column(s).
 */
class LinkMultipleWithColumnsFieldView extends LinkMultipleFieldView {

    /** @const */
    COLUMN_TYPE_VARCHAR = 'varchar'
    /** @const */
    COLUMN_TYPE_ENUM = 'enum'
    /** @const */
    COLUMN_TYPE_BOOL = 'bool'

    /** @inheritDoc */
    setup() {
        super.setup();

        const columnsDefsInitial = this.columnsDefs || {};

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

        const columns = this.getMetadata()
            .get(['entityDefs', this.model.entityType, 'fields', this.name, 'columns']) || {};

        /** @type {string[]} */
        this.columnList = this.columnList || Object.keys(columns);

        this.columnList.forEach(column => {
            if (column in columnsDefsInitial) {
                this.columnsDefs[column] = Espo.Utils.cloneDeep(columnsDefsInitial[column]);

                return;
            }

            if (column in columns) {
                const field = columns[column];

                const o = {};

                o.field = field;
                o.scope = this.foreignScope;

                if (
                    !this.getMetadata().get(['entityDefs', this.foreignScope, 'fields', field, 'type']) &&
                    this.getMetadata().get(['entityDefs', this.model.entityType, 'fields', field, 'type'])
                ) {
                    o.scope = this.model.entityType;
                }

                const fieldDefs = this.getMetadata().get(['entityDefs', o.scope, 'fields', field]) || {};

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
                const id = $(e.currentTarget).data('id');
                const column = $(e.currentTarget).data('column');

                this.toggleBoolColumn(id, column);
            };
        }

        this.on('render', this.disposeColumnAutocompletes, this);
        this.once('remove', this.disposeColumnAutocompletes, this);
    }

    toggleBoolColumn(id, column) {
        this.columns[id][column] = !this.columns[id][column];

        this.reRender();
    }

    /** @inheritDoc */
    getAttributeList() {
        return [
            ...super.getAttributeList(),
            this.name + 'Columns'
        ];
    }

    /**
     * Get an item HTML for detail mode.
     *
     * @param {string} id An ID.
     * @param {string} [name] An name.
     * @return {string}
     */
    getDetailLinkHtml(id, name) {
        // Do not use the `html` method to avoid XSS.

        name = name || this.nameHash[id] || id;

        const $a = $('<a>')
            .attr('href', '#' + this.foreignScope + '/view/' + id)
            .attr('data-id', id)
            .text(name);

        if (this.mode === this.MODE_LIST) {
            $a.addClass('text-default');
        }

        const $el = $('<div>').append($a);

        if (this.isDetailMode()) {
            const iconHtml = this.getIconHtml(id);

            if (iconHtml) {
                $el.prepend(iconHtml);
            }
        }

        this.columnList.forEach(column => {
            const value = (this.columns[id] || {})[column] || '';
            const type = this.columnsDefs[column].type;
            const field = this.columnsDefs[column].field;
            const scope = this.columnsDefs[column].scope;

            if (type !== this.COLUMN_TYPE_ENUM && type !== this.COLUMN_TYPE_VARCHAR) {
                return;
            }

            const text = type === this.COLUMN_TYPE_ENUM ?
                this.getLanguage().translateOption(value, field, scope) :
                value;

            if (!text) {
                return;
            }

            $el.append(
                $('<span>').text(' '),
                $('<span>').addClass('text-muted middle-dot'),
                $('<span>').text(' '),
                $('<span>').text(text).addClass('text-muted small')
            );
        });

        return $el.get(0).innerHTML;
    }

    /** @inheritDoc */
    getValueForDisplay() {
        if (this.isDetailMode() || this.isListMode()) {
            const itemList = [];

            this.ids.forEach(id => {
                itemList.push(
                    this.getDetailLinkHtml(id)
                );
            });

            return itemList
                .map(item => {
                    return $('<div>')
                        .addClass('link-multiple-item')
                        .html(item)
                        .get(0).outerHTML;
                })
                .join('');
        }
    }

    /** @inheritDoc */
    deleteLink(id) {
        this.trigger('delete-link', id);
        this.trigger('delete-link:' + id);

        this.deleteLinkHtml(id);

        const index = this.ids.indexOf(id);

        if (index > -1) {
            this.ids.splice(index, 1);
        }

        delete this.nameHash[id];
        delete this.columns[id];

        this.afterDeleteLink(id);

        this.trigger('change');
    }

    /**
     * Get a column values.
     *
     * @param {string} id An ID.
     * @param {string} column A column.
     * @return {*}
     */
    getColumnValue(id, column) {
        return (this.columns[id] || {})[column];
    }

    addLink(id, name) {
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
    }

    /**
     * @param {string} column
     * @param {string} id
     * @param {*} value
     * @return {JQuery}
     */
    getJQSelect(column, id, value) {
        // Do not use the `html` method to avoid XSS.

        const field = this.columnsDefs[column].field;
        const scope = this.columnsDefs[column].scope;
        const options = this.columnsDefs[column].options || [];

        const $select = $('<select>')
            .addClass('role form-control input-sm')
            .attr('data-id', id)
            .attr('data-column', column);

        options.forEach(itemValue => {
            const text = this.getLanguage().translateOption(itemValue, field, scope);

            const $option = $('<option>')
                .val(itemValue)
                .text(text);

            if (itemValue === (value || '')) {
                $option.attr('selected', 'selected');
            }

            $select.append($option);
        })

        return $select;
    }

    /**
     * @param {string} column
     * @param {string} id
     * @param {*} value
     * @return {JQuery}
     */
    getJQInput(column, id, value) {
        // Do not use the `html` method to avoid XSS.

        const field = this.columnsDefs[column].field;
        const scope = this.columnsDefs[column].scope;
        const maxLength = this.columnsDefs[column].maxLength;

        const text = this.translate(field, 'fields', scope);

        const $input = $('<input>')
            .addClass('role form-control input-sm')
            .attr('data-column', column)
            .attr('placeholder', text)
            .attr('data-id', id)
            .attr('value', value || '');

        if (maxLength) {
            $input.attr('maxlength', maxLength);
        }

        return $input;
    }

    /**
     * @param {string} column
     * @param {string} id
     * @param {*} value
     * @return {JQuery}
     */
    getJQLi(column, id, value) {
        // Do not use the `html` method to avoid XSS.

        const field = this.columnsDefs[column].field;
        const scope = this.columnsDefs[column].scope;

        const text = this.translate(field, 'fields', scope);

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
    }

    /** @inheritDoc */
    addLinkHtml(id, name) {
        if (this.isSearchMode()) {
            return super.addLinkHtml(id, name);
        }

        // Do not use the `html` method to avoid XSS.

        const $container = this.$el.find('.link-container');

        const $el = $('<div>')
            .addClass('form-inline clearfix')
            .addClass('list-group-item link-with-role link-group-item-with-columns')
            .addClass('link-' + id);

        const $remove = $('<a>')
            .attr('role', 'button')
            .attr('tabindex', '0')
            .attr('data-id', id)
            .attr('data-action', 'clearLink')
            .addClass('pull-right')
            .append(
                $('<span>').addClass('fas fa-times')
            );

        const $name = $('<div>')
            .addClass('link-item-name')
            .text(name)
            .append('&nbsp;');

        const $columnList = [];
        const $liList = [];

        this.columnList.forEach(column => {
            const value = (this.columns[id] || {})[column];

            const type = this.columnsDefs[column].type;

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

        const $left = $('<div>');
        const $right = $('<div>');

        $columnList.forEach($item => $left.append(
            $('<span>')
                .addClass('link-item-column')
                .addClass('link-item-column-' + $item.get(0).tagName.toLowerCase())
                .append($item)
        ));

        if ($liList.length) {
            const $ul = $('<ul>').addClass('dropdown-menu');

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

                if ($column.get(0) && $column.get(0).tagName === 'SELECT') {
                    Select.init($column);
                }

                const fetch = ($target) => {
                    if (!$target || !$target.length) {
                        return;
                    }

                    const column = $target.data('column');
                    let value = $target.val().toString().trim();
                    const id = $target.data('id');

                    if (value === '') {
                        value = null;
                    }

                    this.columns[id] = this.columns[id] || {};
                    this.columns[id][column] = value;
                };

                $column.on('change', e => {
                    const $target = $(e.currentTarget);

                    fetch($target);
                    this.trigger('change');
                });

                fetch($column);
            });

            this.initAutocomplete(id);
        }

        return $el;
    }

    initAutocomplete(id) {
        if (!this._autocompleteList) {
            /** @type {Autocomplete[]} */
            this._autocompleteList = [];
        }

        this.columnList.forEach(column => {
            const type = this.columnsDefs[column].type;

            if (type !== this.COLUMN_TYPE_VARCHAR) {
                return;
            }

            const options = this.columnsDefs[column].options;

            if (!(options && options.length)) {
                return;
            }

            const $element = this.$el.find(`[data-column="${column}"][data-id="${id}"]`);

            if (!$element.length) {
                return;
            }

            const autocomplete = new Autocomplete($element.get(0), {
                name: this.name + 'Column' + id,
                triggerSelectOnValidInput: true,
                autoSelectFirst: true,
                handleFocusMode: 1,
                focusOnSelect: true,
                onSelect: () => {
                    this.trigger('change');
                    $element.trigger('change');
                },
                lookup: options,
            });

            this._autocompleteList.push(autocomplete);

            this.once('delete-link:' + id, () => autocomplete.dispose());
        });
    }

    disposeColumnAutocompletes() {
        if (this._autocompleteList && this._autocompleteList.length) {
            this._autocompleteList.forEach(autocomplete =>{
                autocomplete.dispose()
            });

            this._autocompleteList = [];
        }
    }

    // noinspection JSUnusedGlobalSymbols
    validateColumnPattern() {
        let result = false;

        const columnList = this.columnList
            .filter(column => this.columnsDefs[column].type === this.COLUMN_TYPE_VARCHAR)
            .filter(column => this.columnsDefs[column].pattern);

        for (const column of columnList) {
            for (const id of this.ids) {
                const value = this.getColumnValue(id, column);

                if (!value) {
                    continue;
                }

                if (this.validateColumnPatternValue(id, column, value)) {
                    result = true;
                }
            }
        }

        return result;
    }

    validateColumnPatternValue(id, column, value) {
        const pattern = this.columnsDefs[column].pattern;
        const field = this.columnsDefs[column].field;
        const scope = this.columnsDefs[column].scope;

        const helper = new RegExpPattern();

        const result = helper.validate(pattern, value, field, scope);

        if (!result) {
            return false;
        }

        this.showValidationMessage(result.message, '[data-column="' + column + '"][data-id="' + id + '"]');

        return true;
    }

    fetch() {
        const data = super.fetch();

        data[this.columnsName] = Espo.Utils.cloneDeep(this.columns);

        // noinspection JSValidateTypes
        return data;
    }
}

export default LinkMultipleWithColumnsFieldView;
