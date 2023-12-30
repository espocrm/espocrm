/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

import View from 'view';

class StreamView extends View {

    template ='stream'
    filterList = ['all', 'posts', 'updates']
    filter = false

    events = {
        /** @this StreamView */
        'click button[data-action="refresh"]': function () {
            if (!this.getRecordView()) {
                return;
            }

            this.getRecordView().showNewRecords();
        },
        /** @this StreamView */
        'click button[data-action="selectFilter"]': function (e) {
            const data = $(e.currentTarget).data();

            this.actionSelectFilter(data);
        },
    }

    data() {
        let filter = this.filter;

        if (filter === false) {
            filter = 'all';
        }

        return {
            displayTitle: this.options.displayTitle,
            filterList: this.filterList,
            filter: filter,
        };
    }

    setup() {
        this.filter = this.options.filter || this.filter;

        this.wait(
            this.getModelFactory().create('Note', model => {
                this.createView('createPost', 'views/stream/record/edit', {
                    selector: '.create-post-container',
                    model: model,
                    interactiveMode: true,
                }, view => {
                    this.listenTo(view, 'after:save', () => this.getRecordView().showNewRecords());
                });
            })
        );
    }

    afterRender() {
        Espo.Ui.notify(' ... ');

        this.getCollectionFactory().create('Note', collection => {
            this.collection = collection;
            collection.url = 'Stream';

            this.setFilter(this.filter);

            collection.fetch().then(() => {
                this.createView('list', 'views/stream/record/list', {
                    selector: '.list-container',
                    collection: collection,
                    isUserStream: true,
                }, view => {
                    view.notify(false);

                    view.render()
                        .then(view => {
                            view.$el.find('> .list > .list-group');
                        });
                });
            });
        });
    }

    /**
     * @return {module:views/stream/record/list}
     */
    getRecordView() {
        return this.getView('list');
    }

    actionSelectFilter(data) {
        const name = data.name;
        const filter = name;

        let internalFilter = name;

        if (filter === 'all') {
            internalFilter = false;
        }

        this.filter = internalFilter;
        this.setFilter(this.filter);

        this.filterList.forEach((item) => {
            const $el = this.$el.find('.page-header button[data-action="selectFilter"][data-name="' + item + '"]');

            if (item === filter) {
                $el.addClass('active');
            } else {
                $el.removeClass('active');
            }
        });

        let url = '#Stream';

        if (this.filter) {
            url += '/' + filter;
        }

        this.getRouter().navigate(url);

        Espo.Ui.notify(' ... ');

        this.listenToOnce(this.collection, 'sync', () => {
            Espo.Ui.notify(false);
        });

        this.collection.reset();
        this.collection.fetch();
    }

    setFilter(filter) {
        this.collection.data.filter = null;

        if (filter) {
            this.collection.data.filter = filter;
        }

        this.collection.offset = 0;
        this.collection.maxSize = this.getConfig().get('recordsPerPage') || this.collection.maxSize;
    }
}

export default StreamView;
