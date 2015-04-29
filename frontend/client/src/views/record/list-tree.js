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
 ************************************************************************/

Espo.define('Views.Record.ListTree', 'Views.Record.List', function (Dep) {

    return Dep.extend({

        template: 'record.list-tree',

        showMore: false,

        showCount: false,

        checkboxes: false,

        selectable: false,

        rowActionsView: false,

        presentationType: 'tree',

        header: false,

        listContainerEl: ' > .list > ul',

        checkAllResultDisabled: true,

        massActionList: ['remove'],

        createDisabled: false,

        data: function () {
            var data = Dep.prototype.data.call(this);
            data.createDisabled = this.createDisabled;

            return data;
        },

        setup: function () {
            Dep.prototype.setup.call(this);
            if ('createDisabled' in this.options) {
                this.createDisabled = this.options.createDisabled;
            }
        },

        buildRows: function (callback) {
            this.checkedList = [];
            this.rows = [];

            if (this.collection.length > 0) {
                this.wait(true);

                var modelList = this.collection.models;
                var count = modelList.length;
                var built = 0;
                modelList.forEach(function (model, i) {
                    this.createView('row-' + i, 'Record.ListTreeItem', {
                        model: model,
                        collection: this.collection,
                        el: this.options.el + ' ' + this.getRowSelector(model.id),
                        createDisabled: this.createDisabled
                    }, function () {
                        this.rows.push('row-' + i);
                        built++;
                        if (built == count) {
                            if (typeof callback == 'function') {
                                callback();
                            }
                            this.wait(false);
                        };
                    }.bind(this));
                }, this);

            } else {
                if (typeof callback == 'function') {
                    callback();
                }
            }
        },

        getRowSelector: function (id) {
            return 'li[data-id="' + id + '"]';
        },

        getItemEl: function (model, item) {
            return this.options.el + ' li[data-id="' + model.id + '"] span.cell-' + item.name;
        }

    });
});

