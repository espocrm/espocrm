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

/** @module views/modals/related-records */

import ModalView from 'views/modal';
import SearchManager from 'search-manager';
import $ from 'jquery';
import SelectRelatedHelper from 'helpers/record/select-related';
import CreateRelatedHelper from 'helpers/record/create-related';

/**
 * A related-list modal.
 *
 * @todo JSDocs constructor options.
 */
class RelatedListModalView extends ModalView {

    template = 'modals/related-list'

    className = 'dialog dialog-record'
    searchPanel = true
    scope = ''
    noCreateScopeList = ['User', 'Team', 'Role', 'Portal']
    backdrop = true
    fixedHeaderHeight = true
    mandatorySelectAttributeList = null
    layoutName = 'listSmall'

    /** @inheritDoc */
    shortcutKeys = {
        /** @this RelatedListModalView */
        'Control+Space': function (e) {
            this.handleShortcutKeyCtrlSpace(e);
        },
        /** @this RelatedListModalView */
        'Control+Slash': function (e) {
            this.handleShortcutKeyCtrlSlash(e);
        },
        /** @this RelatedListModalView */
        'Control+Comma': function (e) {
            this.handleShortcutKeyCtrlComma(e);
        },
        /** @this RelatedListModalView */
        'Control+Period': function (e) {
            this.handleShortcutKeyCtrlPeriod(e);
        },
        /** @this RelatedListModalView */
        'Control+ArrowLeft': function () {
            this.handleShortcutKeyControlArrowLeft();
        },
        /** @this RelatedListModalView */
        'Control+ArrowRight': function () {
            this.handleShortcutKeyControlArrowRight();
        },
    }

    events = {
        /** @this RelatedListModalView */
        'click button[data-action="createRelated"]': function () {
            this.actionCreateRelated();
        },
        /** @this RelatedListModalView */
        'click .action': function (e) {
            const isHandled = Espo.Utils.handleAction(this, e.originalEvent, e.currentTarget);

            if (isHandled) {
                return;
            }

            this.trigger('action', e.originalEvent, e.currentTarget);
        },
    }

    setup() {
        this.primaryFilterName = this.options.primaryFilterName || null;

        this.buttonList = [
            {
                name: 'cancel',
                label: 'Close',
            }
        ];

        this.scope = this.options.scope || this.options.entityType || this.scope;

        this.defaultOrderBy = this.options.defaultOrderBy;
        this.defaultOrder = this.options.defaultOrder;

        this.panelName = this.options.panelName;
        this.link = this.options.link;

        this.defs = this.options.defs || {};

        this.filterList = this.options.filterList;
        this.filter = this.options.filter;
        this.layoutName = this.options.layoutName || this.layoutName;
        this.url = this.options.url;
        this.listViewName = this.options.listViewName;
        this.rowActionsView = this.options.rowActionsView;

        this.createDisabled = this.options.createDisabled || this.createDisabled;
        this.selectDisabled = this.options.selectDisabled || this.selectDisabled;

        this.massUnlinkDisabled = this.options.massUnlinkDisabled || this.massUnlinkDisabled;

        this.massActionRemoveDisabled = this.options.massActionRemoveDisabled ||
            this.massActionRemoveDisabled;

        this.massActionMassUpdateDisabled = this.options.massActionMassUpdateDisabled ||
            this.massActionMassUpdateDisabled;

        this.panelCollection = this.options.panelCollection;

        if (this.options.searchPanelDisabled) {
            this.searchPanel = false;
        }

        if (this.panelCollection) {
            this.listenTo(this.panelCollection, 'sync', (c, r, o) => {
                if (o.skipCollectionSync) {
                    return;
                }

                this.collection.fetch();
            });

            // Sync changing models.
            this.listenTo(this.panelCollection, 'change', (m, o) => {
                // Prevent change after save.
                if (o.xhr || !m.id) {
                    return;
                }

                const model = this.collection.get(m.id);

                if (!model) {
                    return;
                }

                const attributes = {};

                for (const name in m.attributes) {
                    if (m.hasChanged(name)) {
                        attributes[name] = m.attributes[name];
                    }
                }

                model.set(attributes);
            });

            if (this.model) {
                this.listenTo(this.model, 'after:unrelate', () => {
                    this.panelCollection.fetch({
                        skipCollectionSync: true,
                    });
                });
            }
        }
        else if (this.model) {
            this.listenTo(this.model, 'after:relate', () => {
                this.collection.fetch();
            });
        }

        if (this.noCreateScopeList.indexOf(this.scope) !== -1) {
            this.createDisabled = true;
        }

        this.primaryFilterName = this.filter;

        if (!this.createDisabled) {
            if (
                !this.getAcl().check(this.scope, 'create') ||
                this.getMetadata().get(['clientDefs', this.scope, 'createDisabled'])
            ) {
                this.createDisabled = true;
            }
        }

        this.unlinkDisabled = this.unlinkDisabled || this.options.unlinkDisabled || this.defs.unlinkDisabled;

        if (!this.massUnlinkDisabled) {
            if (this.unlinkDisabled || this.defs.massUnlinkDisabled || this.defs.unlinkDisabled) {
                this.massUnlinkDisabled = true;
            }

            if (!this.getAcl().check(this.model, 'edit')) {
                this.massUnlinkDisabled = true;
            }
        }

        if (!this.selectDisabled) {
            this.buttonList.unshift({
                name: 'selectRelated',
                label: 'Select',
                pullLeft: true,
            });
        }

        if (!this.createDisabled) {
            this.buttonList.unshift({
                name: 'createRelated',
                label: 'Create',
                pullLeft: true,
            });
        }

        this.$header = $('<span>');

        if (this.model) {
            if (this.model.get('name')) {
                this.$header.append(
                    $('<span>').text(this.model.get('name')),
                    ' <span class="chevron-right"></span> '
                );
            }
        }

        let title = this.options.title;

        if (title) {
            title = this.getHelper().escapeString(this.options.title)
                .replace(/@right/, '<span class="chevron-right"></span>');
        }

        this.$header.append(
            title ||
            $('<span>').text(
                this.getLanguage().translate(this.link, 'links', this.entityType)
            )
        );

        if (this.options.listViewUrl) {
            this.$header = $('<a>')
                .attr('href', this.options.listViewUrl)
                .append(this.$header);
        }

        if (
            !this.options.listViewUrl &&
            (
                !this.defs.fullFormDisabled && this.link && this.model.hasLink(this.link) ||
                this.options.fullFormUrl
            )
        ) {
            const url = this.options.fullFormUrl ||
                '#' + this.model.entityType + '/related/' + this.model.id + '/' + this.link;

            this.buttonList.unshift({
                name: 'fullForm',
                label: 'Full Form',
                onClick: () => this.getRouter().navigate(url, {trigger: true}),
            });

            this.$header = $('<a>')
                .attr('href', url)
                .append(this.$header);
        }

        const iconHtml = this.getHelper().getScopeColorIconHtml(this.scope);

        if (iconHtml) {
            this.$header = $('<span>')
                .append(iconHtml)
                .append(this.$header);
        }

        this.waitForView('list');

        if (this.searchPanel) {
            this.waitForView('search');
        }

        this.getCollectionFactory().create(this.scope, collection => {
            collection.maxSize = this.options.maxSize || this.getConfig().get('recordsPerPage');
            collection.url = this.url;

            collection.setOrder(this.defaultOrderBy, this.defaultOrder, true);
            collection.parentModel = this.model;

            this.collection = collection;

            if (this.panelCollection) {
                this.listenTo(collection, 'change', (model) => {
                    const panelModel = this.panelCollection.get(model.id);

                    if (panelModel) {
                        panelModel.set(model.attributes);
                    }
                });

                this.listenTo(collection, 'after:mass-remove', () => {
                    this.panelCollection.fetch({
                        skipCollectionSync: true,
                    });
                });
            }

            this.setupSearch();
            this.setupList();
        });

        // If the list not yet loaded.
        this.once('close', () => {
            if (
                this.collection.lastSyncPromise &&
                this.collection.lastSyncPromise.getStatus() < 4
            ) {
                Espo.Ui.notify(false);
            }

            this.collection.abortLastFetch();
        });
    }

    setFilter(filter) {
        this.searchManager.setPrimary(filter);
    }

    /**
     * @protected
     * @return {module:views/record/search}
     */
    getSearchView() {
        return this.getView('search');
    }

    /**
     * @protected
     * @return {module:views/record/list}
     */
    getRecordView() {
        return this.getView('list');
    }

    setupSearch() {
        this.searchManager = new SearchManager(this.collection, {emptyOnReset: true});

        const primaryFilterName = this.primaryFilterName;

        if (primaryFilterName) {
            this.searchManager.setPrimary(primaryFilterName);
        }

        this.collection.where = this.searchManager.getWhere();

        let filterList = Espo.Utils.clone(this.getMetadata().get(['clientDefs', this.scope, 'filterList']) || []);

        if (this.options.noDefaultFilters) {
            filterList = [];
        }

        if (this.filterList) {
            this.filterList.forEach(item1 => {
                let isFound = false;

                const name1 = item1.name || item1;

                if (!name1 || name1 === 'all') {
                    return;
                }

                filterList.forEach(item2 => {
                    const name2 = item2.name || item2;

                    if (name1 === name2) {
                        isFound = true;
                    }
                });

                if (!isFound) {
                    filterList.push(item1);
                }
            });
        }

        if (this.options.filtersDisabled) {
            filterList = [];
        }

        if (this.searchPanel) {
            this.createView('search', 'views/record/search', {
                collection: this.collection,
                fullSelector: this.containerSelector + ' .search-container',
                searchManager: this.searchManager,
                disableSavePreset: true,
                filterList: filterList,
                filtersLayoutName: this.options.filtersLayoutName,
            }, view => {
                this.listenTo(view, 'reset', () => {});
            });
        }
    }

    setupList() {
        const viewName =
            this.listViewName ||
            this.getMetadata().get(['clientDefs', this.scope, 'recordViews', 'listRelated']) ||
            this.getMetadata().get(['clientDefs', this.scope, 'recordViews', 'list']) ||
            'views/record/list';

        // noinspection JSUnresolvedReference
        const rowActionList = this.defs.rowActionList;

        const promise = this.createView('list', viewName, {
            collection: this.collection,
            fullSelector: this.containerSelector + ' .list-container',
            rowActionsView: this.rowActionsView,
            listLayout: this.options.listLayout,
            layoutName: this.layoutName,
            searchManager: this.searchManager,
            buttonsDisabled: true,
            skipBuildRows: true,
            model: this.model,
            unlinkMassAction: !this.massUnlinkDisabled,
            massActionsDisabled: this.options.massActionsDisabled,
            massActionRemoveDisabled: this.massActionRemoveDisabled,
            massActionMassUpdateDisabled: this.massActionMassUpdateDisabled,
            mandatorySelectAttributeList: this.mandatorySelectAttributeList,
            additionalRowActionList: rowActionList,
            rowActionsOptions: {
                unlinkDisabled: this.unlinkDisabled,
                editDisabled: this.defs.editDisabled,
                removeDisabled: this.defs.removeDisabled,
            },
            removeDisabled: this.defs.removeDisabled,
            forcePagination: this.options.forcePagination,
            pagination: this.getConfig().get('listPagination') ||
                this.getMetadata().get(['clientDefs', this.scope, 'listPagination']) ||
                null,
        }, /** import('views/record/list').default */view => {

            this.listenTo(view, 'after:paginate', () => this.bodyElement.scrollTop = 0);
            this.listenTo(view, 'sort', () => this.bodyElement.scrollTop = 0);

            this.listenToOnce(view, 'select', model => {
                this.trigger('select', model);

                this.close();
            });

            if (this.multiple) {
                this.listenTo(view, 'check', () => {
                    view.checkedList.length ?
                        this.enableButton('select') :
                        this.disableButton('select');
                });

                this.listenTo(view, 'select-all-results', () => this.enableButton('select'));
            }

            const fetch = () => {
                this.whenRendered().then(() => {
                    Espo.Ui.notifyWait();

                    this.collection.fetch()
                        .then(() => Espo.Ui.notify(false));
                });
                // Timeout to make notify work.
                /*setTimeout(() => {
                    Espo.Ui.notifyWait();

                    this.collection.fetch()
                        .then(() => Espo.Ui.notify(false));
                }, 1);*/
            };

            if (this.options.forceSelectAllAttributes || this.forceSelectAllAttributes) {
                fetch();

                return;
            }

            view.getSelectAttributeList(selectAttributeList => {
                if (!~selectAttributeList.indexOf('name')) {
                    selectAttributeList.push('name');
                }

                const mandatorySelectAttributeList = this.options.mandatorySelectAttributeList ||
                    this.mandatorySelectAttributeList || [];

                mandatorySelectAttributeList.forEach(attribute => {
                    if (!~selectAttributeList.indexOf(attribute)) {
                        selectAttributeList.push(attribute);
                    }
                });

                if (selectAttributeList) {
                    this.collection.data.select = selectAttributeList.join(',');
                }

                fetch();
            });
        });

        this.wait(promise);
    }

    // noinspection JSUnusedGlobalSymbols
    actionUnlinkRelated(data) {
        const id = data.id;

        this.confirm({
            message: this.translate('unlinkRecordConfirmation', 'messages'),
            confirmText: this.translate('Unlink'),
        }, () => {
            Espo.Ui.notifyWait();

            Espo.Ajax.deleteRequest(this.collection.url, {id: id}).then(() => {
                Espo.Ui.success(this.translate('Unlinked'));

                this.collection.fetch();

                this.model.trigger('after:unrelate');
                this.model.trigger('after:unrelate:' + this.link);
            });
        });
    }

    /**
     * @private
     */
    actionCreateRelated() {
        // noinspection JSUnresolvedReference
        const actionName = this.defs.createAction || 'createRelated';

        if (actionName === 'createRelated') {
            const helper = new CreateRelatedHelper(this);
            helper.process(this.model, this.link);

            return;
        }

        const methodName = 'action' + Espo.Utils.upperCaseFirst(actionName);

        let p = this.getParentView();

        let view = null;

        while (p) {
            if (p[methodName]) {
                view = p;

                break;
            }

            p = p.getParentView();
        }

        p[methodName]({
            link: this.link,
            scope: this.scope,
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionSelectRelated() {
        // noinspection JSUnresolvedReference
        const actionName = this.defs.selectAction || 'selectRelated';

        if (actionName === 'selectRelated') {
            const helper = new SelectRelatedHelper(this);
            helper.process(this.model, this.link);

            return;
        }

        const methodName = 'action' + Espo.Utils.upperCaseFirst(actionName);

        let p = this.getParentView();

        let view = null;

        while (p) {
            if (p[methodName]) {
                view = p;

                break;
            }

            p = p.getParentView();
        }

        p[methodName]({
            link: this.link,
            primaryFilterName: this.defs.selectPrimaryFilterName,
            boolFilterList: this.defs.selectBoolFilterList,
            massSelect: this.defs.massSelect,
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionRemoveRelated(data) {
        const id = data.id;

        this.confirm({
            message: this.translate('removeRecordConfirmation', 'messages'),
            confirmText: this.translate('Remove'),
        }, () => {
            const model = this.collection.get(id);

            Espo.Ui.notifyWait();

            model
                .destroy()
                .then(() => {
                    Espo.Ui.success(this.translate('Removed'));

                    this.collection.fetch();

                    this.model.trigger('after:unrelate');
                    this.model.trigger('after:unrelate:' + this.link);
                });
        });
    }

    /**
     * @protected
     * @param {KeyboardEvent} e
     */
    handleShortcutKeyCtrlSlash(e) {
        if (!this.searchPanel) {
            return;
        }

        const $search = this.$el.find('input.text-filter').first();

        if (!$search.length) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        $search.focus();
    }

    /**
     * @protected
     * @param {KeyboardEvent} e
     */
    handleShortcutKeyCtrlSpace(e) {
        if (this.createDisabled) {
            return;
        }

        if (this.buttonList.findIndex(item => item.name === 'createRelated' && !item.hidden) === -1) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        this.actionCreateRelated();
    }

    /**
     * @protected
     */
    handleShortcutKeyCtrlComma() {
        if (!this.getSearchView()) {
            return;
        }

        this.getSearchView().selectPreviousPreset();
    }

    /**
     * @protected
     */
    handleShortcutKeyCtrlPeriod() {
        if (!this.getSearchView()) {
            return;
        }

        this.getSearchView().selectNextPreset();
    }

    /**
     * @protected
     */
    handleShortcutKeyControlArrowLeft() {
        this.getRecordView().trigger('request-page', 'previous');
    }

    /**
     * @protected
     */
    handleShortcutKeyControlArrowRight() {
        this.getRecordView().trigger('request-page', 'next');
    }
}

export default RelatedListModalView;
