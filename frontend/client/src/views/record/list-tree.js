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

        rowActionsView: false,

        presentationType: 'tree',

        header: false,

        listContainerEl: ' > .list > ul',

        checkAllResultDisabled: true,

        massActionList: ['remove'],

        selectable: false,

        createDisabled: false,

        selectedData: null,

        level: 0,

        data: function () {
            var data = Dep.prototype.data.call(this);
            data.createDisabled = this.createDisabled;

            return data;
        },

        setup: function () {
            if ('selectable' in this.options) {
                this.selectable = this.options.selectable;
            }
            if ('createDisabled' in this.options) {
                this.createDisabled = this.options.createDisabled;
            }
            if ('level' in this.options) {
                this.level = this.options.level;
            }
            if (this.level == 0) {
                this.selectedData = {
                    id: null,
                    path: [],
                    names: {}
                };
            }
            if ('selectedData' in this.options) {
                this.selectedData = this.options.selectedData;
            }
            Dep.prototype.setup.call(this);

            if (this.selectable) {
                this.on('select', function (o) {
                    if (o.id) {
                        this.$el.find('a.link[data-id="'+o.id+'"]').addClass('text-bold');

                        if (this.level == 0) {
                            this.$el.find('a.link').removeClass('text-bold');
                            this.$el.find('a.link[data-id="'+o.id+'"]').addClass('text-bold');

                            this.setSelected(o.id);
                        }
                    }
                    this.getParentView().trigger('select', o);
                }, this);
            }
        },

        setSelected: function (id) {
            this.rows.forEach(function (key) {
                var view = this.getView(key);

                if (view.model.id == id) {
                    view.setIsSelected();
                } else {
                    view.isSelected = false;
                }
                if (view.hasView('children')) {
                    view.getView('children').setSelected(id);
                }
            }, this);
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
                    this.rows.push('row-' + i);
                    this.createView('row-' + i, 'Record.ListTreeItem', {
                        model: model,
                        collection: this.collection,
                        el: this.options.el + ' ' + this.getRowSelector(model.id),
                        createDisabled: this.createDisabled,
                        level: this.level,
                        isSelected: model.id == this.selectedData.id,
                        selectedData: this.selectedData,
                        selectable: this.selectable
                    }, function () {
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
        },

        actionCreate: function () {
            var parentId = null;
            var parentName = null;
            if (this.model) {
                parentId = this.model.id;
                parentName = this.model.get('name');
            }

            var scope = this.collection.name;

            var viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.edit') || 'Modals.Edit';
            this.createView('quickCreate', viewName, {
                scope: scope,
                attributes: {
                    parentId: parentId,
                    parentName: parentName,
                    order: this.collection.length + 1
                }
            }, function (view) {
                view.render();
                this.listenToOnce(view, 'after:save', function (model) {
                    view.close();
                    model.set('childCollection', this.collection.createSeed());
                    this.collection.push(model);
                    this.buildRows(function () {
                        this.render();
                    }.bind(this));
                }, this);
            }.bind(this));
        },

    });
});

