/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('views/fields/array', ['views/fields/base', 'lib!Selectize'], function (Dep) {

    return Dep.extend({

        type: 'array',

        listTemplate: 'fields/array/detail',

        detailTemplate: 'fields/array/detail',

        editTemplate: 'fields/array/edit',

        searchTemplate: 'fields/array/search',

        data: function () {
            var itemHtmlList = [];
            (this.selected || []).forEach(function (value) {
                itemHtmlList.push(this.getItemHtml(value));
            }, this);

            return _.extend({
                selected: this.selected,
                translatedOptions: this.translatedOptions,
                hasOptions: this.params.options ? true : false,
                itemHtmlList: itemHtmlList
            }, Dep.prototype.data.call(this));
        },

        events: {
            'click [data-action="removeValue"]': function (e) {
                var value = $(e.currentTarget).data('value');
                this.removeValue(value);
            },
            'click [data-action="showAddModal"]': function () {
                var options = [];

                this.params.options.forEach(function (item) {
                    if (!~this.selected.indexOf(item)) {
                        options.push(item);
                    }
                }, this);
                this.createView('addModal', 'Modals.ArrayFieldAdd', {
                    options: options,
                    translatedOptions: this.translatedOptions
                }, function (view) {
                    view.render();
                    this.listenToOnce(view, 'add', function (item) {
                        this.addValue(item);
                        view.close();
                    }.bind(this));
                }.bind(this));
            }
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.noEmptyString = this.params.noEmptyString;

            this.listenTo(this.model, 'change:' + this.name, function () {
                this.selected = Espo.Utils.clone(this.model.get(this.name));
            }, this);

            this.selected = Espo.Utils.clone(this.model.get(this.name) || []);
            if (Object.prototype.toString.call(this.selected) !== '[object Array]')    {
                this.selected = [];
            }

            this.setupOptions();

            if ('translatedOptions' in this.options) {
                this.translatedOptions = this.options.translatedOptions;
            }
            if ('translatedOptions' in this.params) {
                this.translatedOptions = this.params.translatedOptions;
            }

            if (!this.translatedOptions) {
                var t = {};
                if (this.params.translation) {
                    var data = this.getLanguage().data;
                    var arr = this.params.translation.split('.');
                    var pointer = this.getLanguage().data;
                    arr.forEach(function (key) {
                        if (key in pointer) {
                            pointer = pointer[key];
                            t = pointer;
                        }
                    }, this);
                } else {
                    t = this.translate(this.name, 'options', this.model.name);
                }
                this.translatedOptions = null;
                var translatedOptions = {};
                if (this.params.options) {
                    this.params.options.forEach(function (o) {
                        if (typeof t === 'object' && o in t) {
                            translatedOptions[o] = t[o];
                        } else {
                            translatedOptions[o] = o;
                        }
                    }.bind(this));
                    this.translatedOptions = translatedOptions;
                }
            }

            if (this.options.customOptionList) {
                this.setOptionList(this.options.customOptionList);
            }
        },

        setupOptions: function () {

        },

        setOptionList: function (optionList) {
            if (!this.originalOptionList) {
                this.originalOptionList = this.params.options;
            }
            this.params.options = Espo.Utils.clone(optionList);

            if (this.mode == 'edit') {
                if (this.isRendered()) {
                    this.reRender();
                    this.trigger('change');
                } else {
                    this.once('after:render', function () {
                        this.trigger('change');
                    }, this);
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

            if (this.mode == 'edit') {
                if (this.isRendered()) {
                    this.reRender();
                }
            }
        },

        afterRender: function () {
            if (this.mode == 'edit') {
                this.$list = this.$el.find('.list-group');
                var $select = this.$select = this.$el.find('.select');

                if (!this.params.options) {
                    $select.on('keypress', function (e) {
                        if (e.keyCode == 13) {
                            var value = $select.val();
                            if (this.noEmptyString) {
                                if (value == '') {
                                    return;
                                }
                            }
                            this.addValue(value);
                            $select.val('');
                        }
                    }.bind(this));
                }

                this.$list.sortable({
                    stop: function () {
                        this.fetchFromDom();
                        this.trigger('change');
                    }.bind(this)
                });
            }

            if (this.mode == 'search') {
                this.renderSearch();
            }
        },

        renderSearch: function () {
            var $element = this.$element = this.$el.find('[name="' + this.name + '"]');

            var valueList = this.searchParams.valueFront || [];
            this.$element.val(valueList.join(':,:'));

            var data = [];
            (this.params.options || []).forEach(function (value) {
                var label = this.getLanguage().translateOption(value, this.name, this.scope);
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

            this.$element.selectize({
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
        },

        fetchFromDom: function () {
            var selected = [];
            this.$el.find('.list-group .list-group-item').each(function (i, el) {
                selected.push($(el).data('value'));
            });
            this.selected = selected;
        },

        getValueForDisplay: function () {
            return this.selected.map(function (item) {
                if (this.translatedOptions != null) {
                    if (item in this.translatedOptions) {
                        return this.translatedOptions[item];
                    }
                }
                return item;
            }, this).join(', ');
        },

        getItemHtml: function (value) {
            if (this.translatedOptions != null) {
                for (var item in this.translatedOptions) {
                    if (this.translatedOptions[item] == value) {
                        value = item;
                        break;
                    }
                }
            }

            var label = value;
            if (this.translatedOptions) {
                label = ((value in this.translatedOptions) ? this.translatedOptions [value]: value);
            }
            var html = '<div class="list-group-item" data-value="' + value + '" style="cursor: default;">' + label +
            '&nbsp;<a href="javascript:" class="pull-right" data-value="' + value + '" data-action="removeValue"><span class="glyphicon glyphicon-remove"></a>' +
            '</div>';

            return html;
        },

        addValue: function (value) {
            if (this.selected.indexOf(value) == -1) {
                var html = this.getItemHtml(value);
                this.$list.append(html);
                this.selected.push(value);
                this.trigger('change');
            }
        },

        removeValue: function (value) {
            this.$list.children('[data-value="' + value + '"]').remove();
            var index = this.selected.indexOf(value);
            this.selected.splice(index, 1);
            this.trigger('change');
        },

        fetch: function () {
            var data = {};
            data[this.name] = Espo.Utils.clone(this.selected || []);
            return data;
        },

        fetchSearch: function () {
            var field = this.name;
            var arr = [];
            var arrFront = [];

            var list = this.$element.val().split(':,:');
            if (list.length == 1 && list[0] == '') {
                list = [];
            }

            list.forEach(function(value) {
                arr.push({
                    type: 'like',
                    field: field,
                    value: "%" + value + "%"
                });
                arrFront.push(value);
            });

            if (arr.length == 0) {
                return false;
            }

            var data = {
                type: 'or',
                value: arr,
                valueFront: arrFront
            };
            return data;
        },

        validateRequired: function () {
            if (this.isRequired()) {
                var value = this.model.get(this.name);
                if (!value || value.length == 0) {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                    this.showValidationMessage(msg);
                    return true;
                }
            }
        },

    });
});


