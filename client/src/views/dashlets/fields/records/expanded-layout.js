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

Espo.define('views/dashlets/fields/records/expanded-layout', 'views/fields/base', function (Dep) {

    return Dep.extend({

        listTemplate: 'dashlets/fields/records/expanded-layout/edit',

        detailTemplate: 'dashlets/fields/records/expanded-layout/edit',

        editTemplate: 'dashlets/fields/records/expanded-layout/edit',

        delimiter: ':,:',

        setup: function () {
            Dep.prototype.setup.call(this);
        },

        getRowHtml: function (row, i) {
            row = row || [];
            var list = [];
            row.forEach(function (item) {
                list.push(item.name);
            });
            return '<div><input type="text" class="row-'+i.toString()+'" value="'+list.join(this.delimiter)+'"></div>';
        },

        afterRender: function () {
            this.$container = this.$el.find('>.layout-container');
            var rowList = (this.model.get(this.name) || {}).rows || [];

            rowList = Espo.Utils.cloneDeep(rowList);

            rowList.push([]);

            var fieldDataList = this.getFieldDataList();

            rowList.forEach(function (row, i) {
                var rowHtml = this.getRowHtml(row, i);

                var $row = $(rowHtml);

                this.$container.append($row);

                $input = $row.find('input');

                $input.selectize({
                    options: fieldDataList,
                    delimiter: this.delimiter,
                    labelField: 'label',
                    valueField: 'value',
                    highlight: false,
                    searchField: ['label'],
                    plugins: ['remove_button', 'drag_drop'],
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

                $input.on('change', function () {
                    this.trigger('change');
                    this.reRender();
                }.bind(this));
            }, this);
        },

        getFieldDataList: function () {
            var scope = this.model.get('entityType') || this.getMetadata().get(['dashlets', this.model.dashletName, 'entityType']);
            if (!scope) return [];

            var fields = this.getMetadata().get(['entityDefs', scope, 'fields']) || {};

            var fieldList = Object.keys(fields).sort(function (v1, v2) {
                 return this.translate(v1, 'fields', scope).localeCompare(this.translate(v2, 'fields', scope));
            }.bind(this)).filter(function (item) {
                if (fields[item].disabled || fields[item].listLayoutDisabled) return false;
                return true;
            }, this);

            var dataList = [];

            fieldList.forEach(function (item) {
                dataList.push({
                    value: item,
                    label: this.translate(item, 'fields', scope)
                });
            }, this);
            return dataList;
        },

        fetch: function () {
            var value = {
                rows: []
            };
            this.$el.find('input').each(function (i, el) {
                var row = [];
                var list = ($(el).val() || '').split(this.delimiter);
                if (list.length == 1 && list[0] == '') {
                    list = [];
                }
                if (list.length === 0) return;
                list.forEach(function (item) {
                    var o = {name: item};
                    if (item === 'name') {
                        o.link = true;
                    }
                    row.push(o);
                }, this);
                value.rows.push(row);
            }.bind(this));

            var data = {};
            data[this.name] = value;

            return data;
        }

    });

});
