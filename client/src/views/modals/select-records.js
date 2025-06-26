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

/** @module views/modals/select-records */

import ModalView from 'views/modal';
import SearchManager from 'search-manager';
import RecordModal from 'helpers/record-modal';

/**
 * A select-records modal.
 */
class SelectRecordsModalView extends ModalView {

    template = 'modals/select-records'

    cssName = 'select-modal'
    className = 'dialog dialog-record'
    multiple = false
    createButton = true
    searchPanel = true
    scope = ''
    noCreateScopeList = ['User', 'Team', 'Role', 'Portal']
    layoutName = 'listSmall'

    /** @inheritDoc */
    shortcutKeys = {
        /** @this SelectRecordsModalView */
        'Control+Enter': function (e) {
            this.handleShortcutKeyCtrlEnter(e);
        },
        /** @this SelectRecordsModalView */
        'Control+Space': function (e) {
            this.handleShortcutKeyCtrlSpace(e);
        },
        /** @this SelectRecordsModalView */
        'Control+Slash': function (e) {
            this.handleShortcutKeyCtrlSlash(e);
        },
        /** @this SelectRecordsModalView */
        'Control+Comma': function (e) {
            this.handleShortcutKeyCtrlComma(e);
        },
        /** @this SelectRecordsModalView */
        'Control+Period': function (e) {
            this.handleShortcutKeyCtrlPeriod(e);
        },
    }

    events = {
        /** @this SelectRecordsModalView */
        'click button[data-action="create"]': function () {
            this.create();
        },
        /** @this SelectRecordsModalView */
        'click .list a': function (e) {
            e.preventDefault();
        },
    }

    /**
     * @typedef {Object} module:views/modals/select-records~Options
     * @property {string} entityType An entity type.
     * @property {Object.<string, module:search-manager~advancedFilter>} [filters] Filters.
     * @property {string[]} [boolFilterList] Bool filters.
     * @property {string} [primaryFilterName] A primary filter.
     * @property {string[]} [filterList] A filter list.
     * @property {string} [layoutName] A layout name.
     * @property {boolean} [multiple] Allow select multiple.
     * @property {boolean} [createButton] A create button.
     * @property {boolean} [massRelateEnabled] Mass-relate.
     * @property {string} [orderBy] An order-by.
     * @property {'asc'|'desc'} [orderDirection] An order direction.
     * @property {boolean} [forceSelectAllAttributes] Force select all attributes.
     * @property {string[]} [mandatorySelectAttributeList] Mandatory attributes to select.
     * @property {function(): Promise<Record>} [createAttributesProvider] Create-attributes provider.
     * @property {Record} [createAttributes] Create-attributes.
     * @property {function(import('model').default[])} [onSelect] On record select. As of 9.0.0.
     * @property {function({where: Record[], searchParams: module:collection~Data})} [onMassSelect]
     *     On record select. As of 9.1.0.
     * @property {function()} [onCreate] On create click. As of 9.0.5.
     * @property {boolean} [searchPanelDisabled] Disable the search panel.
     */

    /**
     *
     * @param {module:views/modals/select-records~Options | module:views/modal~Options} options
     */
    constructor(options) {
        super(options);

        if (options.onSelect) {
            /** @private */
            this.onSelect = options.onSelect;
        }

        if (options.onMassSelect) {
            /** @private */
            this.onMassSelect = options.onMassSelect;
        }

        if (options.onCreate) {
            /** @private */
            this.onCreate = options.onCreate;
        }

        /** @private */
        this.createAttributesProvider = options.createAttributesProvider;

        this.options = options;
    }

    data() {
        return {
            createButton: this.createButton,
            createText: this.translate('Create ' + this.scope, 'labels', this.scope),
        };
    }

    setup() {
        this.filters = this.options.filters || {};
        this.boolFilterList = this.options.boolFilterList;
        this.primaryFilterName = this.options.primaryFilterName || null;
        this.filterList = this.options.filterList || this.filterList || null;
        this.layoutName = this.options.layoutName || this.layoutName;

        if ('multiple' in this.options) {
            this.multiple = this.options.multiple;
        }

        if ('createButton' in this.options) {
            this.createButton = this.options.createButton;
        }

        this.massRelateEnabled = this.options.massRelateEnabled;

        this.buttonList = [
            {
                name: 'cancel',
                label: 'Cancel',
            },
        ];

        if (this.multiple) {
            this.buttonList.unshift({
                name: 'select',
                style: 'danger',
                label: 'Select',
                disabled: true,
                title: 'Ctrl+Enter',
            });
        }

        // noinspection JSUnresolvedReference
        this.scope = this.entityType = this.options.scope || this.scope || this.options.entityType;

        const orderBy = this.options.orderBy ||
            this.getMetadata().get(['clientDefs', this.scope, 'selectRecords', 'orderBy']);

        const order = this.options.orderDirection ||
            this.getMetadata().get(['clientDefs', this.scope, 'selectRecords', 'order']);

        if (orderBy) {
            this.defaultOrderBy = orderBy;
            this.defaultOrder = order || false;
        }

        if (this.noCreateScopeList.indexOf(this.scope) !== -1) {
            this.createButton = false;
        }

        if (this.createButton) {
            if (
                !this.getAcl().check(this.scope, 'create') ||
                this.getMetadata().get(['clientDefs', this.scope, 'createDisabled'])
            ) {
                this.createButton = false;
            }
        }

        if (this.createButton) {
            this.addButton({
                name: 'create',
                position: 'right',
                onClick: () => this.create(),
                iconClass: 'fas fa-plus fa-sm',
                label: 'Create',
            });
        }

        if (this.getMetadata().get(['clientDefs', this.scope, 'searchPanelDisabled'])) {
            this.searchPanel = false;
        }

        if (this.getUser().isPortal()) {
            if (this.getMetadata().get(['clientDefs', this.scope, 'searchPanelInPortalDisabled'])) {
                this.searchPanel = false;
            }
        }

        if (this.options.searchPanelDisabled) {
            this.searchPanel = false;
        }

        if (!this.options.headerText) {
            this.$header = $('<span>');

            this.$header.append(
                $('<span>').text(
                    this.translate('Select') + ' · ' +
                    this.getLanguage().translate(this.scope, 'scopeNamesPlural')
                )
            );

            this.$header.prepend(
                this.getHelper().getScopeColorIconHtml(this.scope)
            );
        }

        this.waitForView('list');

        if (this.searchPanel) {
            this.waitForView('search');
        }

        this.getCollectionFactory().create(this.scope, (collection) => {
            collection.maxSize = this.getConfig().get('recordsPerPageSelect') || 5;

            this.collection = collection;

            if (this.defaultOrderBy) {
                this.collection.setOrder(this.defaultOrderBy, this.defaultOrder || 'asc', true);
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

    setupSearch() {
        this.searchManager = new SearchManager(this.collection, {emptyOnReset: true});

        if (this.filters) {
            this.searchManager.setAdvanced(this.filters);
        }

        const boolFilterList = this.boolFilterList ||
            this.getMetadata().get(`clientDefs.${this.scope}.selectDefaultFilters.boolFilterList`);

        if (boolFilterList) {
            this.searchManager.setBool(boolFilterList);
        }

        const primaryFilterName = this.primaryFilterName ||
            this.getMetadata().get(`clientDefs.${this.scope}.selectDefaultFilters.filter`);

        if (primaryFilterName) {
            this.searchManager.setPrimary(primaryFilterName);
        }

        this.collection.where = this.searchManager.getWhere();

        if (this.searchPanel) {
            this.createView('search', 'views/record/search', {
                collection: this.collection,
                fullSelector: this.containerSelector + ' .search-container',
                searchManager: this.searchManager,
                disableSavePreset: true,
                filterList: this.filterList,
            }, view => {
                this.listenTo(view, 'reset', () => {});
            });
        }
    }

    setupList() {
        const viewName = this.getMetadata().get(`clientDefs.${this.scope}.recordViews.listSelect`) ||
            this.getMetadata().get(`clientDefs.${this.scope}.recordViews.list`) ||
            'views/record/list';

        /** @type {module:views/record/list~options} */
        const options = {
            collection: this.collection,
            fullSelector: this.containerSelector + ' .list-container',
            selectable: true,
            checkboxes: this.multiple,
            massActionsDisabled: true,
            rowActionsView: null,
            layoutName: this.layoutName,
            checkAllResultDisabled: !this.massRelateEnabled,
            buttonsDisabled: true,
            skipBuildRows: true,
            pagination: this.getMetadata().get(['clientDefs', this.scope, 'listPagination']) || null,
        }

        const promise = this.createView('list', viewName, options, view => {
            this.listenToOnce(view, 'select', models => {
                this.trigger('select', models);

                if (this.onSelect) {
                    if (!Array.isArray(models)) {
                        models = [models];
                    }

                    this.onSelect(models);
                }

                this.close();
            });

            if (this.multiple) {
                this.listenTo(view, 'check', () => {
                    if (view.checkedList.length) {
                        this.enableButton('select');
                    } else {
                        this.disableButton('select');
                    }
                });

                this.listenTo(view, 'select-all-results', () => {
                    this.enableButton('select');
                });
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

    async create() {
        if (this.onCreate) {
            this.onCreate();

            return;
        }

        // @todo Remove in v10.0. Kept bc.
        // noinspection JSUnresolvedReference
        if (this.options.triggerCreateEvent) {
            this.trigger('create');

            return;
        }

        let attributes;

        if (this.options.createAttributesProvider) {
            attributes = await this.createAttributesProvider();
        } else {
            attributes = this.options.createAttributes || {};
        }

        const helper = new RecordModal();

        await helper.showCreate(this, {
            entityType: this.entityType,
            fullFormDisabled: true,
            attributes: attributes,
            afterSave: model => {
                this.trigger('select', model);

                if (this.onSelect) {
                    this.onSelect([model]);
                }

                setTimeout(() => this.close(), 10);
            },
            beforeRender: view => {
                this.listenToOnce(view, 'leave', () => {
                    view.close();
                    this.close();
                });
            },
        });
    }

    actionSelect() {
        if (!this.multiple) {
            return;
        }

        const listView = this.getRecordView();

        if (listView.allResultIsChecked) {
            const data = {
                massRelate: true,
                where: listView.getWhereForAllResult(),
                searchParams: this.collection.data,
            };

            this.trigger('select', data);

            if (this.onMassSelect) {
                this.onMassSelect(data)
            }

            this.close();

            return;
        }

        const list = listView.getSelected();

        if (list.length) {
            this.trigger('select', list);

            if (this.onSelect) {
                this.onSelect(list);
            }
        }

        this.close();
    }

    /**
     * @protected
     * @return {import('views/record/search').default}
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
    handleShortcutKeyCtrlEnter(e) {
        if (!this.multiple) {
            return;
        }

        if (!this.hasAvailableActionItem('select')) {
            return;
        }

        e.stopPropagation();
        e.preventDefault();

        this.actionSelect();
    }

    /**
     * @protected
     * @param {KeyboardEvent} e
     */
    handleShortcutKeyCtrlSpace(e) {
        if (!this.createButton) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        this.create();
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
}

export default SelectRecordsModalView;
