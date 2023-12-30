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

import BaseDashletView from 'views/dashlets/abstract/base';
import SearchManager from 'search-manager';

class RecordListDashletView extends BaseDashletView {

    templateContent = '<div class="list-container">{{{list}}}</div>'

    /**
     * A scope.
     * @type {string}
     */
    scope

    listView = null
    listViewColumn = 'views/record/list'
    listViewExpanded = 'views/record/list-expanded'
    layoutType = 'expanded'

    optionsFields = {
        title: {
            type: 'varchar',
            required: true,
        },
        autorefreshInterval: {
            type: 'enumFloat',
            options: [0, 0.5, 1, 2, 5, 10],
        },
        displayRecords: {
            type: 'enumInt',
            options: [3, 4, 5, 10, 15],
        },
    }

    rowActionsView = 'views/record/row-actions/view-and-edit'

    init() {
        super.init();

        this.scope = this.getMetadata().get(['dashlets', this.name, 'entityType']) || this.scope;
    }

    checkAccess() {
        return this.getAcl().check(this.scope, 'read');
    }

    /**
     * @return {module:search-manager~data}
     */
    getSearchData() {
        return this.getOption('searchData');
    }

    afterRender() {
        this.getCollectionFactory().create(this.scope, collection => {
            const searchData = this.getSearchData();

            this.searchManager = new SearchManager(collection, 'list', null, this.getDateTime(), searchData);

            if (!this.scope) {
                this.$el.find('.list-container')
                    .html(this.translate('selectEntityType', 'messages', 'DashletOptions'));

                return;
            }

            if (!this.checkAccess()) {
                this.$el.find('.list-container').html(this.translate('No Access'));

                return;
            }

            if (this.collectionUrl) {
                collection.url = this.collectionUrl;
            }

            this.collection = collection;

            collection.orderBy = this.getOption('orderBy') || this.getOption('sortBy') || this.collection.orderBy;

            if (this.getOption('orderBy')) {
                collection.order = 'asc';
            }

            if (this.hasOption('asc')) {
                collection.order = this.getOption('asc') ? 'asc' : false;
            }

            if (this.getOption('sortDirection') === 'asc') {
                collection.order = 'asc';
            } else if (this.getOption('sortDirection') === 'desc') {
                collection.order = 'desc';
            }

            if (this.getOption('order') === 'asc') {
                collection.order = 'asc';
            }
            else if (this.getOption('order') === 'desc') {
                collection.order = 'desc';
            }

            collection.maxSize = this.getOption('displayRecords');
            collection.where = this.searchManager.getWhere();

            const viewName = this.listView || ((this.layoutType === 'expanded') ?
                this.listViewExpanded : this.listViewColumn);

            this.createView('list', viewName, {
                collection: collection,
                selector: '.list-container',
                pagination: this.getOption('pagination') ? 'bottom' : false,
                type: 'listDashlet',
                rowActionsView: this.rowActionsView,
                checkboxes: false,
                showMore: true,
                listLayout: this.getOption(this.layoutType + 'Layout'),
                skipBuildRows: true,
            }, (view) => {
                view.getSelectAttributeList(selectAttributeList => {
                    if (selectAttributeList) {
                        collection.data.select = selectAttributeList.join(',');
                    }

                    collection.fetch();
                });
            });
        });
    }

    setupActionList() {
        if (this.scope && this.getAcl().checkScope(this.scope, 'create')) {
            this.actionList.unshift({
                name: 'create',
                text: this.translate('Create ' + this.scope, 'labels', this.scope),
                iconHtml: '<span class="fas fa-plus"></span>',
                url: '#'+this.scope+'/create',
            });
        }
    }

    actionRefresh() {
        if (!this.collection) {
            return;
        }

        this.collection.where = this.searchManager.getWhere();
        this.collection.fetch({
            previousDataList: this.collection.models.map(model => {
                return Espo.Utils.cloneDeep(model.attributes);
            }),
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionCreate() {
        const attributes = this.getCreateAttributes() || {};

        if (this.getOption('populateAssignedUser')) {
            if (this.getMetadata().get(['entityDefs', this.scope, 'fields', 'assignedUsers'])) {
                attributes['assignedUsersIds'] = [this.getUser().id];
                attributes['assignedUsersNames'] = {};
                attributes['assignedUsersNames'][this.getUser().id] = this.getUser().get('name');
            } else {
                attributes['assignedUserId'] = this.getUser().id;
                attributes['assignedUserName'] = this.getUser().get('name');
            }
        }

        Espo.Ui.notify(' ... ');

        const viewName = this.getMetadata().get('clientDefs.' + this.scope + '.modalViews.edit') ||
            'views/modals/edit';

        this.createView('modal', viewName, {
            scope: this.scope,
            attributes: attributes,
        }, view => {
            view.render();
            view.notify(false);

            this.listenToOnce(view, 'after:save', () => {
                this.actionRefresh();
            });
        });
    }

    getCreateAttributes() {}
}

export default RecordListDashletView;
