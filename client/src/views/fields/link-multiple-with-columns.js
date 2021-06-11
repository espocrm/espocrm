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

define('views/fields/link-multiple-with-columns', 'views/fields/link-multiple', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            var columnsDefsInitial = this.columnsDefs || {};

            this.columnsDefs = {};

            this.columnsName = this.name + 'Columns';

            this.columns = Espo.Utils.cloneDeep(this.model.get(this.columnsName) || {});

            this.listenTo(this.model, 'change:' + this.columnsName, function () {
                this.columns = Espo.Utils.cloneDeep(this.model.get(this.columnsName) || {});
            }, this);

            var columns = this.getMetadata()
                .get(['entityDefs', this.model.name, 'fields', this.name, 'columns']) || {};

            var columnList = Object.keys(columns);

            this.columnList = this.columnList || columnList;

            this.columnList.forEach(function (column) {
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
            }, this);

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

        getAttributeList: function () {
            var list = Dep.prototype.getAttributeList.call(this);

            list.push(this.name + 'Columns');

            return list;
        },

        getDetailLinkHtml: function (id, name) {
            name = name || this.nameHash[id] || id;

            var roleHtml = '';

            this.columnList.forEach(function (column) {
                var value = (this.columns[id] || {})[column] || '';

                var type = this.columnsDefs[column].type;

                if (value !== '' && value) {
                    if (type === 'enum') {
                        roleHtml += ' <span class="text-muted small">&#187;</span> ' +
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
                        roleHtml += ' <span class="text-muted small">&#187;</span> ' +
                            '<span class="text-muted small">' +
                            this.getHelper().escapeString(value) +
                            '</span>';
                    }
                }
            }, this);

            var iconHtml = '';

            if (this.mode === 'detail') {
                iconHtml = this.getIconHtml();
            }

            var lineHtml = '<div>' + iconHtml + '<a href="#' + this.foreignScope + '/view/' + id + '">' +
                this.getHelper().escapeString(name) + '</a> ' + roleHtml + '</div>';

            return lineHtml;
        },

        getValueForDisplay: function () {
            if (this.mode === 'detail' || this.mode === 'list') {
                var names = [];

                this.ids.forEach(function (id) {
                    var lineHtml = this.getDetailLinkHtml(id);

                    names.push(lineHtml);
                }, this);

                return names.join('');
            }
        },

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

        getColumnValue: function (id, column) {
            return (this.columns[id] || {})[column];
        },

        addLink: function (id, name) {
            if (!~this.ids.indexOf(id)) {
                this.ids.push(id);
                this.nameHash[id] = name;
                this.columns[id] = {};

                this.columnList.forEach(function (column) {
                    this.columns[id][column] = null;
                    if ('default' in this.columnsDefs[column]) {
                        this.columns[id][column] = this.columnsDefs[column].default;
                    }
                }, this);

                this.addLinkHtml(id, name);

                this.afterAddLink(id);

                this.trigger('add-link', id);
                this.trigger('add-link:' + id);
            }

            this.trigger('change');
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
        },

        afterAddLink: function (id) {
            Dep.prototype.afterAddLink.call(this, id);
        },

        getJQSelect: function (column, id, value) {
            var $column = $(
                '<select class="role form-control input-sm pull-right" data-id="'+id+'" data-column="'+column+'">'
            );

            this.columnsDefs[column].options.forEach(function (item) {
                var selectedHtml = (item == value) ? 'selected': '';

                option = '<option value="' + item + '" '+selectedHtml+'>' +
                    this.getLanguage().translateOption(
                        item,
                    this.columnsDefs[column].field,
                    this.columnsDefs[column].scope
                    ) +
                    '</option>';

                $column.append(option);
            }, this);

            return $column;
        },

        addLinkHtml: function (id, name) {
            if (this.mode === 'search') {
                return Dep.prototype.addLinkHtml.call(this, id, name);
            }

            var $container = this.$el.find('.link-container');

            var $el = $(
                '<div class="form-inline list-group-item link-with-role link-group-item-with-columns clearfix">'
            ).addClass('link-' + id);

            var nameHtml = '<div class="link-item-name">' + this.getHelper().escapeString(name) + '&nbsp;' + '</div>';

            var removeHtml = '<a href="javascript:" class="pull-right" data-id="' + id + '" data-action="clearLink">' +
                '<span class="fas fa-times"></a>';

            var columnFormElementJQList = [];
            var columnMenuItemJQList = [];

            this.columnList.forEach(function (column) {
                var value = (this.columns[id] || {})[column];
                var escapedValue = Handlebars.Utils.escapeExpression(value);

                var type = this.columnsDefs[column].type;
                var field = this.columnsDefs[column].field;
                var scope = this.columnsDefs[column].scope;

                var $column;

                if (type === 'enum') {
                    $column = this.getJQSelect(column, id, escapedValue);
                    columnFormElementJQList.push($column);

                }
                else if (type === 'varchar') {
                    var label = this.translate(field, 'fields', scope);

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
                    var label = this.translate(field, 'fields', scope);

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
            }, this);

            $left = $('<div>');

            if (columnFormElementJQList.length === 1) {
                $left.append(columnFormElementJQList[0]);
            }
            else {
                columnFormElementJQList.forEach(function ($input) {
                    $left.append($input);
                }, this);
            }

            if (columnMenuItemJQList.length) {
                var $ul = $('<ul class="dropdown-menu">');

                columnMenuItemJQList.forEach(function ($item) {
                    $ul.append($item);
                }, this);

                $left.append(
                    $('<div class="btn-group pull-right">').append(
                        $('<button type="button" class="btn btn-link btn-sm dropdown-toggle" data-toggle="dropdown">')
                            .append(
                                '<span class="caret">'
                            )
                    ).append($ul)
                );
            }

            $left.append(nameHtml);
            $el.append($left);


            $right = $('<div>');

            $right.append(removeHtml);
            $el.append($right);

            $container.append($el);

            if (this.mode === 'edit') {
                columnFormElementJQList.forEach(function ($column) {
                    var fetch = function ($target) {
                        if (!$target || !$target.length) {
                            return;
                        }

                        var column = $target.data('column');

                        var value = $target.val().toString().trim();

                        var id = $target.data('id');

                        this.columns[id] = this.columns[id] || {};

                        this.columns[id][column] = value;
                    }.bind(this);

                    $column.on('change', function (e) {
                        var $target = $(e.currentTarget);

                        fetch($target);

                        this.trigger('change');
                    }.bind(this));

                    fetch($column);
                }, this);

                this.initAutocomplete(id);
            }

            return $el;
        },

        initAutocomplete: function (id) {
            if (!this._autocompleteElementList) {
                this._autocompleteElementList = [];
            }

            this.columnList.forEach(function (column) {
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
                            beforeRender: function (c) {
                                c.addClass('small');
                            },
                            formatResult: function (suggestion) {
                                return this.getHelper().escapeString(suggestion.value);
                            }.bind(this),

                            lookupFilter: function (suggestion, query, queryLowerCase) {
                                if (suggestion.value.toLowerCase().indexOf(queryLowerCase) === 0) {
                                    if (suggestion.value.length === queryLowerCase.length) {
                                        return false;
                                    }

                                    return true;
                                }

                                return false;
                            },
                            onSelect: function () {
                                this.trigger('change');

                                $element.trigger('change');
                            }.bind(this)
                        });
                        $element.attr('autocomplete', 'espo-' + this.name + '-' + column + '-' + id);

                        $element.on('focus', function () {
                            if ($element.val()) {
                                return;
                            }

                            $element.autocomplete('onValueChange');
                        });

                        this._autocompleteElementList.push($element);

                        this.once('delete-link:' + id, function () {
                            $element.autocomplete('dispose');
                        });
                    }
                }
            }, this);
        },

        disposeColumnAutocompletes: function () {
            if (this._autocompleteElementList && this._autocompleteElementList.length) {
                this._autocompleteElementList.forEach(function ($el) {
                    $el.autocomplete('dispose');
                }, this);

                this._autocompleteElementList = [];
            }
        },

        fetch: function () {
            var data = Dep.prototype.fetch.call(this);

            data[this.columnsName] = Espo.Utils.cloneDeep(this.columns);

            return data;
        },

    });
});
