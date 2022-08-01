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

define('views/dashlets/fields/records/expanded-layout', ['views/fields/base', 'ui/multi-select'],
function (Dep, /** module:ui/multi-select*/MultiSelect) {

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

            let list = [];

            row.forEach(item => {
                list.push(item.name);
            });

            return $('<div>')
                .append(
                    $('<input>')
                        .attr('type', 'text')
                        .addClass('row-' + i.toString())
                        .attr('value', list.join(this.delimiter))
                )
                .get(0).outerHTML;
        },

        afterRender: function () {
            this.$container = this.$el.find('>.layout-container');

            let rowList = (this.model.get(this.name) || {}).rows || [];

            rowList = Espo.Utils.cloneDeep(rowList);

            rowList.push([]);

            let fieldDataList = this.getFieldDataList();

            rowList.forEach((row, i) => {
                let rowHtml = this.getRowHtml(row, i);
                let $row = $(rowHtml);

                this.$container.append($row);

                let $input = $row.find('input');

                /** @type {module:ui/multi-select~Options} */
                let multiSelectOptions = {
                    items: fieldDataList,
                    delimiter: this.delimiter,
                    matchAnyWord: this.matchAnyWord,
                    draggable: true,
                };

                MultiSelect.init($input, multiSelectOptions);

                /*$input.selectize({
                    options: fieldDataList,
                    delimiter: this.delimiter,
                    labelField: 'label',
                    valueField: 'value',
                    highlight: false,
                    searchField: ['label'],
                    plugins: ['remove_button', 'drag_drop'],
                    score: function (search) {
                        let score = this.getScoreFunction(search);
                        search = search.toLowerCase();
                        return function (item) {
                            if (item.label.toLowerCase().indexOf(search) === 0) {
                                return score(item);
                            }

                            return 0;
                        };
                    }
                });*/

                $input.on('change', () => {
                    this.trigger('change');
                    this.reRender();
                });
            });
        },

        getFieldDataList: function () {
            var scope = this.model.get('entityType') ||
                this.getMetadata().get(['dashlets', this.model.dashletName, 'entityType']);

            if (!scope) {
                return [];
            }

            let fields = this.getMetadata().get(['entityDefs', scope, 'fields']) || {};

            let fieldList = Object.keys(fields)
                .sort((v1, v2) => {
                     return this.translate(v1, 'fields', scope)
                         .localeCompare(this.translate(v2, 'fields', scope));
                })
                .filter(item => {
                    if (fields[item].disabled || fields[item].listLayoutDisabled) {
                        return false;
                    }

                    return true;
                });

            let dataList = [];

            fieldList.forEach(item => {
                dataList.push({
                    value: item,
                    label: this.translate(item, 'fields', scope),
                });
            });

            return dataList;
        },

        fetch: function () {
            var value = {
                rows: [],
            };

            this.$el.find('input').each((i, el) => {
                let row = [];
                let list = ($(el).val() || '').split(this.delimiter);

                if (list.length === 1 && list[0] === '') {
                    list = [];
                }

                if (list.length === 0) {
                    return;
                }

                list.forEach(item => {
                    let o = {name: item};

                    if (item === 'name') {
                        o.link = true;
                    }

                    row.push(o);
                });

                value.rows.push(row);
            });

            let data = {};

            data[this.name] = value;

            return data;
        },
    });
});
