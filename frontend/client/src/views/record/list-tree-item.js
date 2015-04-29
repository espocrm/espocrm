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

Espo.define('Views.Record.ListTreeItem', 'View', function (Dep) {

    return Dep.extend({

        template: 'record.list-tree-item',

        isEnd: false,

        data: function () {
            return {
                name: this.model.get('name'),
                isUnfolded: this.isUnfolded,
                isEnd: this.isEnd
            };
        },

        events: {
            'click [data-action="unfold"]': function (e) {
                this.unfold();
                e.stopPropagation();
            },
            'click [data-action="fold"]': function (e) {
                this.fold();
                e.stopPropagation();
            }
        },

        setup: function () {
            this.isUnfolded = false;
            this.scope = this.model.name;

            var childCollection = this.model.get('childCollection');

            if (this.isUnfolded) {
                if (childCollection) {
                    this.createChildren();
                }
            } else {
                if (childCollection === false) {
                    this.isEnd = true;
                }
            }
        },

        createChildren: function () {
            var childCollection = this.model.get('childCollection');
            var callback = null;
            if (this.isRendered()) {
                callback = function (view) {
                    view.render();
                };
            }
            this.createView('children', 'Record.ListTree', {
                collection: childCollection,
                el: this.options.el + ' > .children',
                createDisabled: this.options.createDisabled
            }, callback);
        },

        unfold: function () {
            var childCollection = this.model.get('childCollection');
            if (childCollection !== false) {
                if (childCollection !== null) {
                    this.createChildren();
                    this.isUnfolded = true;
                    this.afterUnfold();
                } else {
                    this.getCollectionFactory().create(this.scope, function (collection) {
                        collection.url = this.collection.url;
                        collection.parentId = this.model.id;
                        collection.maxDepth = 1;

                        this.notify('Please wait...');
                        this.listenToOnce(collection, 'sync', function () {
                            this.notify(false);
                            if (collection.length) {
                                this.model.set('childCollection', collection);
                                this.createChildren();
                                this.isUnfolded = true;
                                this.afterUnfold();
                            } else {
                                this.isEnd = true;
                                this.model.set('childCollection', false);
                                this.afterIsEnd();
                            }
                        }, this);
                        collection.fetch()
                    }, this);
                }
            }
        },

        fold: function () {
            this.clearView('children');
            this.afterFold();
        },

        afterFold: function () {
            this.$el.find('a[data-action="fold"][data-id="'+this.model.id+'"]').addClass('hidden');
            this.$el.find('a[data-action="unfold"][data-id="'+this.model.id+'"]').removeClass('hidden');
            this.$el.find(' > .children').addClass('hidden');
        },

        afterUnfold: function () {
            this.$el.find('a[data-action="unfold"][data-id="'+this.model.id+'"]').addClass('hidden');
            this.$el.find('a[data-action="fold"][data-id="'+this.model.id+'"]').removeClass('hidden');
            this.$el.find(' > .children').removeClass('hidden');
        },

        afterIsEnd: function () {
            this.$el.find('a[data-action="unfold"][data-id="'+this.model.id+'"]').addClass('hidden');
            this.$el.find('a[data-action="fold"][data-id="'+this.model.id+'"]').addClass('hidden');
        },

    });
});

