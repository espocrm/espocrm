/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
import StreamListView from 'views/stream/record/list';
import SearchView from 'views/record/search';
import SearchManager from 'search-manager';

class GlobalStreamView extends View {

    // language=Handlebars
    templateContent = `
        <div class="page-header">
            <div class="row">
                <div class="col-sm-7 col-xs-5">
                    <h3>
                        <span
                            data-action="fullRefresh"
                            style="user-select: none; cursor: pointer"
                        >{{translate 'GlobalStream' category='scopeNames'}}</span>
                    </h3>
                </div>
                <div class="col-sm-5 col-xs-7"></div>
            </div>
        </div>
        <div class="search-container">{{{search}}}</div>
        <div class="row">
            <div class="col-md-8">
                <div class="list-container list-container-panel">{{{list}}}</div>
            </div>
        </div>
    `

    collection

    setup() {
        this.wait(
            (async () => {
                this.collection = await this.getCollectionFactory().create('Note');

                this.collection.url = 'GlobalStream';
                this.collection.maxSize = this.getConfig().get('recordsPerPage');
                this.collection.paginationByNumber = true;

                this.setupSearchManager();
                await this.createSearchView();
            })()
        );

        this.addActionHandler('fullRefresh', () => this.actionFullRefresh());
    }

    setupSearchManager() {
        const searchManager = new SearchManager(this.collection);

        searchManager.loadStored();

        this.collection.where = searchManager.getWhere();
        this.searchManager = searchManager;
    }

    createSearchView() {
        this.searchView = new SearchView({
            collection: this.collection,
            searchManager: this.searchManager,
            isWide: true,
            filtersLayoutName: 'filtersGlobal',
        });

        return this.assignView('search', this.searchView, '.search-container');
    }

    afterRender() {
        if (!this.listView) {
            this.fetchAndRender();
        }
    }

    fetchAndRender() {
        Espo.Ui.notifyWait();

        this.collection.fetch()
            .then(() => {
                this.listView = new StreamListView({
                    collection: this.collection,
                    isUserStream: true,
                });

                this.assignView('list', this.listView, '.list-container')
                    .then(() => {
                        Espo.Ui.notify(false);

                        this.listView.render();
                    });
            });
    }

    /**
     * @private
     */
    async actionFullRefresh() {
        Espo.Ui.notifyWait();

        await this.collection.fetch();

        Espo.Ui.notify();
    }
}

export default GlobalStreamView;
