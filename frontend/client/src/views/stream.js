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

Espo.define('views/stream', 'view', function (Dep) {

    return Dep.extend({

        template: 'stream',

        filterList: ['all', 'posts', 'updates'],

        filter: 'all',

        events: {
            'click button[data-action="refresh"]': function () {
                if (!this.hasView('list')) return;
                this.getView('list').showNewRecords();
            },
            'click button[data-action="selectFilter"]': function (e) {
                var data = $(e.currentTarget).data();
                this.actionSelectFilter(data);
            },
        },

        data: function () {
            return {
                displayTitle: this.options.displayTitle,
                filterList: this.filterList,
                filter: this.filter
            };
        },

        setup: function () {
            this.wait(true);
            this.getModelFactory().create('Note', function (model) {
                this.createView('createPost', 'views/stream/record/edit', {
                    el: this.options.el + ' .create-post-container',
                    model: model,
                    interactiveMode: true
                }, function (view) {
                    this.listenTo(view, 'after:save', function () {
                        this.getView('list').showNewRecords();
                    }, this);
                }, this);
                this.wait(false);
            }, this);
        },

        afterRender: function () {
            this.notify('Loading...');
            this.getCollectionFactory().create('Note', function (collection) {
                this.collection = collection;
                collection.url = 'Stream';

                this.listenToOnce(collection, 'sync', function () {
                    this.createView('list', 'views/stream/record/list', {
                        el: this.options.el + ' .list-container',
                        collection: collection,
                        isUserStream: true,
                    }, function (view) {
                        view.notify(false);
                        view.render();
                    });
                }, this);
                collection.fetch();
            }, this);
        },

        actionSelectFilter: function (data) {
            var name = data.name;

            var filter = name;

            if (name == 'all') {
                filter = false;
            }

            this.setFilter(filter);

            this.filterList.forEach(function (item) {
                var $el = this.$el.find('.page-header button[data-action="selectFilter"][data-name="'+item+'"]');
                if (item === name) {
                    $el.addClass('active');
                } else {
                    $el.removeClass('active');
                }
            }, this);

            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

            this.listenToOnce(this.collection, 'sync', function () {
                Espo.Ui.notify(false);
            }, this);

            this.collection.fetch();
        },

        setFilter: function (filter) {
            this.collection.data.filter = null;
            if (filter) {
                this.collection.data.filter = filter;
            }
        },

    });
});

