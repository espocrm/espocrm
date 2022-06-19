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

define('views/fields/link-multiple-with-columns', ['views/fields/link-multiple'], function (Dep) {

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
         * @inheritDoc
         */
        setup: function () {
            Dep.prototype.setup.call(this);

            var columnsDefsInitial = this.columnsDefs || {};

            this.columnsDefs = {};

            this.columnsName = this.name + 'Columns';

            this.columns = Espo.Utils.cloneDeep(this.model.get(this.columnsName) || {});

            this.listenTo(this.model, 'change:' + this.columnsName, () => {
                this.columns = Espo.Utils.cloneDeep(this.model.get(this.columnsName) || {});
            });

            var columns = this.getMetadata()
                .get(['entityDefs', this.model.name, 'fields', this.name, 'columns']) || {};

            var columnList = Object.keys(columns);

            this.columnList = this.columnList || columnList;

            this.columnList.forEach(column => {
                if (column in columnsDefsInitial) {
                    this.columnsDefs[column] = Espo.Utils.cloneDeep(columnsDefsInitial[column]);

                    return;
                }
                if (column in columns) {
                    var field = columns[column];

                    var o = {};

                    o.field = field;
                    o.scope = this.foreignScope;

                    if (
                        !this.getMetadata().get(['entityDefs', this.foreignScope, 'fields', field, 'type'])
                        &&
                        this.getMetadata().get(['entityDefs', this.model.name, 'fields', field, 'type'])
                    ) {
                        o.scope = this.model.name;
                    }

                    var fieldDefs = this.getMetadata().get(['entityDefs', o.scope, 'fields', field]) || {};

                    o.type = fieldDefs.type;

                    if (o.type === 'enum' || o.type === 'varchar') {
                        o.options = fieldDefs.options;
                    }

                    if ('default' in fieldDefs) {
                        o.default = fieldDefs.default;
                    }

                    if ('maxLength' in fieldDefs) {
                        o.maxLength = fieldDefs.maxLength;
                    }

                    this.columnsDefs[column] = o;
                }
            });

            if (this.mode === 'edit' || this.mode === 'detail') {
                this.events['click a[data-action="toggleBoolColumn"]'] = function (e) {
                    var id = $(e.currentTarget).data('id');
                    var column = $(e.currentTarget).data('column');

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
            var list = Dep.prototype.getAttributeList.call(this);

            list.push(this.name + 'Columns');

            return list;
        },

        /**
         * Get an item HTML for detail mode.
         *
         * @param {string} id An ID.
         * @param {string} name An name.
         * @return {string}
         */
        getDetailLinkHtml: function (id, name) {
            name = name || this.nameHash[id] || id;

            var roleHtml = '';

            this.columnList.forEach(column => {
                var value = (this.columns[id] || {})[column] || '';
                var type = this.columnsDefs[column].type;

                if (value !== '' && value) {
                    if (type === 'enum') {
                        roleHtml += ' <span class="text-muted chevron-right"></span> ' +
                            '<span class="text-muted small">' +
                            this.getHelper()
                                .escapeString(
                                    this.getLanguage().translateOption(
                                        value,
                                    this.columnsDefs[column].field,
                                    this.columnsDefs[column].scope)
                                ) +
                            '</span>';
                    }
                    else if (type === 'varchar') {
                        roleHtml += ' <span class="text-muted chevron-right"></span> ' +
                            '<span class="text-muted small">' +
                            this.getHelper().escapeString(value) +
                            '</span>';
                    }
                }
            });

            var iconHtml = '';

            if (this.mode === 'detail') {
                iconHtml = this.getIconHtml();
            }

            var lineHtml = '<div>' + iconHtml + '<a href="#' + this.foreignScope + '/view/' + id + '">' +
                this.getHelper().escapeString(name) + '</a> ' + roleHtml + '</div>';

            return lineHtml;
        },

        /**
         * @inheritDoc
         */
        getValueForDisplay: function () {
            if (this.mode === 'detail' || this.mode === 'list') {
                var names = [];

                this.ids.forEach(id => {
                    var lineHtml = this.getDetailLinkHtml(id);

                    names.push(lineHtml);
                });

                return names.join('');
            }
        },

        /**
         * @inheritDoc
         */
        deleteLink: function (id) {
            this.trigger('delete-link', id);
            this.trigger('delete-link:' + id);

            this.deleteLinkHtml(id);

            var index = this.ids.indexOf(id);

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

        getJQSelect: function (column, id, value) {
            var $column = $(
                '<select class="role form-control input-sm pull-right" ' +
                'data-id="'+id+'" data-column="'+column+'">'
            );

            this.columnsDefs[column].options.forEach(item =>{
                var selectedHtml = (item == value) ? 'selected': '';

                option = '<option value="' + item + '" '+selectedHtml+'>' +
                    this.getLanguage().translateOption(
                        item,
                    this.columnsDefs[column].field,
                    this.columnsDefs[column].scope
                    ) +
                    '</option>';

                $column.append(option);
            });

            return $column;
        },

        /**
         * @inheritDoc
         */
        addLinkHtml: function (id, name) {
            if (this.mode === 'search') {
                return Dep.prototype.addLinkHtml.call(this, id, name);
            }

            var $container = this.$el.find('.link-container');

            var $el = $(
                '<div class="form-inline list-group-item link-with-role link-group-item-with-columns clearfix">'
            ).addClass('link-' + id);

            var nameHtml = '<div class="link-item-name">' +
                this.getHelper().escapeString(name) + '&nbsp;' + '</div>';

            var removeHtml = '<a href="javascript:" class="pull-right" ' +
                'data-id="' + id + '" data-action="clearLink">' +
                '<span class="fas fa-times"></a>';

            var columnFormElementJQList = [];
            var columnMenuItemJQList = [];

            this.columnList.forEach(column => {
                var value = (this.columns[id] || {})[column];
                var escapedValue = Handlebars.Utils.escapeExpression(value);

                var type = this.columnsDefs[column].type;
                var field = this.columnsDefs[column].field;
                var scope = this.columnsDefs[column].scope;

                var $column;
                var label;

                if (type === 'enum') {
                    $column = this.getJQSelect(column, id, escapedValue);
                    columnFormElementJQList.push($column);

                }
                else if (type === 'varchar') {
                    label = this.translate(field, 'fields', scope);

                    $column = $(
                        '<input class="role form-control input-sm pull-right" ' +
                        'data-column="'+column+'" placeholder="'+label+'" data-id="'+id+'" ' +
                        'value="' + (escapedValue || '') + '">'
                    );

                    if ('maxLength' in this.columnsDefs[column]) {
                        $column.attr('maxLength', this.columnsDefs[column].maxLength);
                    }

                    columnFormElementJQList.push($column);
                }
                else if (type === 'bool') {
                    label = this.translate(field, 'fields', scope);

                    var $menuItem = $('<li>')
                        .append(
                            $('<a href="javascript:" data-action="toggleBoolColumn">')
                                .attr('data-column', column)
                                .attr('data-id', id)
                                .append(
                                    $('<span class="check-icon fas fa-check fa-sm pull-right">')
                                        .addClass(!value ? 'hidden' : '')
                                )
                                .append(
                                    $('<div>').text(label)
                                )
                        );

                    columnMenuItemJQList.push($menuItem);
                }
            });

            let $left = $('<div>');

            if (columnFormElementJQList.length === 1) {
                $left.append(columnFormElementJQList[0]);
            }
            else {
                columnFormElementJQList.forEach($input => {
                    $left.append($input);
                });
            }

            if (columnMenuItemJQList.length) {
                var $ul = $('<ul class="dropdown-menu">');

                columnMenuItemJQList.forEach($item => {
                    $ul.append($item);
                });

                $left.append(
                    $('<div class="btn-group pull-right">')
                        .append(
                            $('<button type="button" class="btn btn-link btn-sm dropdown-toggle" '+
                                'data-toggle="dropdown">')
                                .append(
                                    '<span class="caret">'
                                )
                        )
                        .append($ul)
                );
            }

            $left.append(nameHtml);
            $el.append($left);

            let $right = $('<div>');

            $right.append(removeHtml);
            $el.append($right);

            $container.append($el);

            if (this.mode === 'edit') {
                columnFormElementJQList.forEach($column => {
                    var fetch = ($target) => {
                        if (!$target || !$target.length) {
                            return;
                        }

                        var column = $target.data('column');

                        var value = $target.val().toString().trim();

                        var id = $target.data('id');

                        this.columns[id] = this.columns[id] || {};

                        this.columns[id][column] = value;
                    };

                    $column.on('change', e => {
                        var $target = $(e.currentTarget);

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
                var type = this.columnsDefs[column].type;

                if (type === 'varchar') {
                    var options = this.columnsDefs[column].options;

                    if (options && options.length) {
                        var $element = this.$el.find('[data-column="'+column+'"][data-id="'+id+'"]');

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

        /**
         * @inheritDoc
         */
        fetch: function () {
            var data = Dep.prototype.fetch.call(this);

            data[this.columnsName] = Espo.Utils.cloneDeep(this.columns);

            return data;
        },
    });
});
