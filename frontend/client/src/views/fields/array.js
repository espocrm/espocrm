/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 ************************************************************************/

Espo.define('Views.Fields.Array', 'Views.Fields.Enum', function (Dep) {

    return Dep.extend({

        type: 'array',

        listTemplate: 'fields.array.detail',

        detailTemplate: 'fields.array.detail',

        editTemplate: 'fields.array.edit',

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

            this.listenTo(this.model, 'change:' + this.name, function () {
                this.selected = Espo.Utils.clone(this.model.get(this.name));
            }, this);


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

            this.selected = Espo.Utils.clone(this.model.get(this.name) || []);
            if (Object.prototype.toString.call(this.selected) !== '[object Array]')    {
                this.selected = [];
            }
        },

        afterRender: function () {
            if (this.mode == 'edit' || this.mode == 'search') {
                this.$list = this.$el.find('.list-group');
                var $select = this.$select = this.$el.find('.select');

                if (!this.params.options) {
                    $select.on('keypress', function (e) {
                        if (e.keyCode == 13) {
                            var value = $select.val();
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
            var html = '<div class="list-group-item" data-value="' + value + '">' + label +
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

        validateRequired: function () {
            if (this.params.required || this.model.isRequired(this.name)) {
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


