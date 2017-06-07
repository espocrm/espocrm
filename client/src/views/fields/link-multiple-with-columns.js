/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2017 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

Espo.define('views/fields/link-multiple-with-columns', ['views/fields/link-multiple', 'lib!Selectize'], function (Dep) {

    return Dep.extend({

        searchTemplate: 'fields/link-multiple-with-columns/search',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.columnsName = this.name + 'Columns';
            this.columns = Espo.Utils.cloneDeep(this.model.get(this.columnsName) || {});

            this.listenTo(this.model, 'change:' + this.columnsName, function () {
                this.columns = Espo.Utils.cloneDeep(this.model.get(this.columnsName) || {});
            }, this);

            var columns = this.getMetadata().get('entityDefs.' + this.model.name + '.fields.' + this.name + '.columns');
            if (Object.keys(columns).length) {
                this.defs.columns = new Object();

                for (var name in columns) {
                    if (!columns.hasOwnProperty(name)) {
                        continue;
                    }

                    var field = columns[name];
                    if (field !== null) {
                        fieldScope = this.foreignScope;
                    } else {
                        fieldScope = this.model.name;
                    }

                    var column = new Object();
                    column.field = field;
                    column.scope = fieldScope;
                    column.type = this.getMetadata().get('entityDefs.' + fieldScope + '.fields.' + field + '.type');
                    if (column.type == 'enum') {
                        column.options = this.getMetadata().get('entityDefs.' + fieldScope + '.fields.' + field + '.options');
                    }
                    this.defs.columns[name] = column;
                }
            }
        },

        getAttributeList: function () {
            var list = Dep.prototype.getAttributeList.call(this);
            list.push(this.name + 'Columns');
            return list;
        },

        getDetailLinkHtml: function (id, name) {
            name = name || this.nameHash[id];

            var columnHtml = '';
            for (var column in this.defs.columns) {
                var value = (this.columns[id] || {})[column] || '';
                var def = this.defs.columns[column];
                var columnValue = this.getHelper().stripTags(this.getLanguage().translateOption(value, column, def.scope));
                if (columnValue.length) {
                    columnHtml += ' <span class="text-muted small"> &#187; ' + columnValue + '</span>';
                }
            }
            var lineHtml = '<div>' + '<a href="#' + this.foreignScope + '/view/' + id + '">' + this.getHelper().stripTags(name) + '</a>' + columnHtml + '</div>';
            return lineHtml;
        },

        getValueForDisplay: function () {
            if (this.mode == 'detail' || this.mode == 'list') {
                var names = [];
                this.ids.forEach(function (id) {
                    var lineHtml = this.getDetailLinkHtml(id);
                    names.push(lineHtml);
                }, this);
                return names.join('');
            }
        },

        deleteLink: function (id) {
            this.deleteLinkHtml(id);

            var index = this.ids.indexOf(id);
            if (index > -1) {
                this.ids.splice(index, 1);
            }
            delete this.nameHash[id];
            delete this.columns[id];
            this.trigger('change');
        },

        addLink: function (id, name) {
            if (!~this.ids.indexOf(id)) {
                this.ids.push(id);
                this.nameHash[id] = name;
                this.columns[id] = {};
                for (var column in this.defs.columns) {
                    this.columns[id][column] = null;
                }
                this.addLinkHtml(id, name);
            }
            this.trigger('change');
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.mode == 'search') {
                for (var column in this.defs.columns) {
                    var def = this.defs.columns[column];
                    var valueList = (this.searchParams.columns || [])[column] || [];
                    var $element = this.defs.columns[column].$element = this.$el.find('[data-column="'+column+'"]');
                    $element.val(valueList.join(':,:'));

                    if (def.type == 'enum') {
                        var data = [];

                        (this.defs.columns[column].options || []).forEach(function (value) {
                            var label = this.getLanguage().translateOption(value, column, this.scope);
                            if (this.translatedOptions) {
                                if (value in this.translatedOptions) {
                                    label = this.translatedOptions[value];
                                }
                            }
                            data.push({
                                value: value,
                                label: label
                            });
                        }, this);

                        $element.selectize({
                            options: data,
                            delimiter: ':,:',
                            labelField: 'label',
                            valueField: 'value',
                            highlight: false,
                            searchField: ['label'],
                            plugins: ['remove_button'],
                            score: function (search) {
                                var score = this.getScoreFunction(search);
                                search = search.toLowerCase();
                                return function (item) {
                                    if (item.label.toLowerCase().indexOf(search) === 0) {
                                        return score(item);
                                    }
                                    return 0;
                                };
                            }
                        });
                    }
                }
            }
        },

        getJQSelect: function (column, id, columnValue) {
            var $element = $('<select class="form-control input-sm pull-right" data-column="'+column+'" data-id="'+id+'">');
            var def = this.defs.columns[column];
            def.options.forEach(function (option) {
                var selectedHtml = (option == columnValue) ? 'selected': '';
                optionHtml = '<option value="'+option+'" '+selectedHtml+'>' + this.getLanguage().translateOption(option, def.field, def.scope) + '</option>';
                $element.append(optionHtml);
            }, this);

            return $element;
        },

        addLinkHtml: function (id, name) {
            if (this.mode == 'search') {
                console.log("Search Mode");
                return Dep.prototype.addLinkHtml.call(this, id, name);
            }
            var $container = this.$el.find('.link-container');
            var $el = $('<div class="form-inline list-group-item link-with-columns">').addClass('link-' + id);

            var nameHtml = '<div>' + this.getHelper().stripTags(name) + '&nbsp;' + '</div>';

            var removeHtml = '<a href="javascript:" class="pull-right" data-id="' + id + '" data-action="clearLink"><span class="glyphicon glyphicon-remove"></a>';

            $left = $('<div class="pull-left">').css({
                'width': '92%',
                'display': 'inline-block'
            });

            (Object.keys(this.defs.columns).reverse() || []).forEach(function (column) {
                var def = this.defs.columns[column];
                var value = (this.columns[id] || {})[column];
                var $element;

                value = Handlebars.Utils.escapeExpression(value);

                if (def.type == 'enum') {
                    $element = def.$element = this.getJQSelect(column, id, value);
                } else {
                    var label = this.translate(def.field, 'fields', def.scope);
                    $element = def.$element = $('<input class="column form-control input-sm pull-right" maxlength="50" placeholder="'+label+'" data-column="'+column+'" data-id="'+id+'" value="' + (value || '') + '">');
                }

                if ($element) {
                    $left.append($element);
                }
            }, this);

            $left.append(nameHtml);
            $el.append($left);

            $right = $('<div>').css({
                'width': '8%',
                'display': 'inline-block',
                'vertical-align': 'top'
            });
            $right.append(removeHtml);
            $el.append($right);
            $el.append('<br style="clear: both;" />');

            $container.append($el);

            if (this.mode == 'edit' && Object.keys(this.defs.columns).length) {
                var fetch = function ($target) {
                    if (!$target || !$target.size()) return;

                    var name = $target.attr('data-column');

                    var value = $target.val().toString().trim();
                    var id = $target.data('id');
                    this.columns[id] = this.columns[id] || {};
                    this.columns[id][name] = value;
                }.bind(this);
                for (var column in this.defs.columns) {
                    var def = this.defs.columns[column];
                    def.$element.on('change', function (e) {
                        var $target = $(e.currentTarget);
                        fetch($target);
                        this.trigger('change');
                    }.bind(this));
                    fetch(def.$element);
                }
            }
            return $el;
        },

        fetch: function () {
            var data = Dep.prototype.fetch.call(this);
            data[this.columnsName] = Espo.Utils.cloneDeep(this.columns);
            return data;
        },

        fetchSearch: function () {
            let data = Dep.prototype.fetchSearch.call(this);
            if (typeof(this.defs.columns) !== 'undefined') {
                data.columns = new Object();

                for (var column in this.defs.columns) {
                    var def = this.defs.columns[column];
                    var columnValue = (def.$element.val() || '').split(':,:');
                    if (columnValue.length == 1 && columnValue[0] == '') {
                        columnValue = [];
                    }
                    data.columns[column] = columnValue;
                }
            }

            return data;
        }
    });
});


