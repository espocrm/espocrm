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

import ModalView from 'views/modal';
import SearchManager from 'search-manager';

class ActionHistoryModalView extends ModalView {

    template = 'modals/action-history'

    scope = 'ActionHistoryRecord'
    className = 'dialog dialog-record'
    backdrop = true

    setup() {
        super.setup();

        this.buttonList = [
            {
                name: 'cancel',
                label: 'Close',
            },
        ];

        this.scope = this.entityType = this.options.scope || this.scope;

        this.$header = $('<a>')
            .attr('href', '#ActionHistoryRecord')
            .addClass('action')
            .attr('data-action', 'listView')
            .text(this.getLanguage().translate(this.scope, 'scopeNamesPlural'));

        this.waitForView('list');

        this.getCollectionFactory().create(this.scope, collection => {
            collection.maxSize = this.getConfig().get('recordsPerPage') || 20;
            this.collection = collection;

            this.setupSearch();
            this.setupList();

            collection.fetch();
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionListView() {
        this.getRouter().navigate('#ActionHistoryRecord', {trigger: true});
        this.close();
    }

    setupSearch() {
        this.searchManager = new SearchManager(this.collection);

        this.collection.data.boolFilterList = ['onlyMy'];
        this.collection.where = this.searchManager.getWhere();

        this.createView('search', 'views/record/search', {
            collection: this.collection,
            fullSelector: this.containerSelector + ' .search-container',
            searchManager: this.searchManager,
            disableSavePreset: true,
            textFilterDisabled: true,
        });
    }

    setupList() {
        const viewName = this.getMetadata().get(`clientDefs.${this.scope}.recordViews.list`) ||
            'views/record/list';

        this.listenToOnce(this.collection, 'sync', () => {
            this.createView('list', viewName, {
                collection: this.collection,
                fullSelector: this.containerSelector + ' .list-container',
                selectable: false,
                checkboxes: false,
                massActionsDisabled: true,
                rowActionsView: 'views/record/row-actions/view-only',
                type: 'listSmall',
                searchManager: this.searchManager,
                checkAllResultDisabled: true,
                buttonsDisabled: true,
            });
        });
    }
}

export default ActionHistoryModalView;
