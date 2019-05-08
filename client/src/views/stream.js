/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

define('views/stream', 'view', function (Dep) {

    return Dep.extend({

        template: 'stream',

        filterList: ['all', 'posts', 'updates'],

        filter: false,

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
            var filter = this.filter;
            if (filter === false) {
                filter = 'all';
            }
            return {
                displayTitle: this.options.displayTitle,
                filterList: this.filterList,
                filter: filter
            };
        },

        setup: function () {
            this.filter = this.options.filter || this.filter;

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

                this.setFilter(this.filter);

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

            var internalFilter = name;

            if (filter == 'all') {
                internalFilter = false;
            }

            this.filter = internalFilter;
            this.setFilter(this.filter);

            this.filterList.forEach(function (item) {
                var $el = this.$el.find('.page-header button[data-action="selectFilter"][data-name="'+item+'"]');
                if (item === filter) {
                    $el.addClass('active');
                } else {
                    $el.removeClass('active');
                }
            }, this);

            var url = '#Stream';
            if (this.filter) {
                url += '/' + filter;
            }
            this.getRouter().navigate(url);

            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

            this.listenToOnce(this.collection, 'sync', function () {
                Espo.Ui.notify(false);
            }, this);

            this.collection.reset();
            this.collection.fetch();
        },

        setFilter: function (filter) {
            this.collection.data.filter = null;
            if (filter) {
                this.collection.data.filter = filter;
            }
            this.collection.offset = 0;
            this.collection.maxSize = this.getConfig().get('recordsPerPage') || this.collection.maxSize;
        },

    });
});

