/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/** @module views/modals/select-records */

import ModalView from 'views/modal';
import SearchManager from 'search-manager';

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

    data() {
        return {
            createButton: this.createButton,
            createText: this.translate('Create ' + this.scope, 'labels', this.scope),
        };
    }

    setup() {
        this.filters = this.options.filters || {};
        this.boolFilterList = this.options.boolFilterList || [];
        this.primaryFilterName = this.options.primaryFilterName || null;
        this.filterList = this.options.filterList || this.filterList || null;

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

        this.scope = this.entityType = this.options.scope || this.scope;

        let customDefaultOrderBy = this.getMetadata().get(['clientDefs', this.scope, 'selectRecords', 'orderBy']);
        let customDefaultOrder = this.getMetadata().get(['clientDefs', this.scope, 'selectRecords', 'order']);

        if (customDefaultOrderBy) {
            this.defaultOrderBy = customDefaultOrderBy;
            this.defaultOrder = customDefaultOrder || false;
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

        if (this.getMetadata().get(['clientDefs', this.scope, 'searchPanelDisabled'])) {
            this.searchPanel = false;
        }

        if (this.getUser().isPortal()) {
            if (this.getMetadata().get(['clientDefs', this.scope, 'searchPanelInPortalDisabled'])) {
                this.searchPanel = false;
            }
        }

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
            Espo.Ui.notify(false);

            this.collection.abortLastFetch();
        });
    }

    setupSearch() {
        let searchManager = this.searchManager =
            new SearchManager(this.collection, 'listSelect', null, this.getDateTime());

        searchManager.emptyOnReset = true;

        if (this.filters) {
            searchManager.setAdvanced(this.filters);
        }

        let boolFilterList = this.boolFilterList ||
            this.getMetadata().get('clientDefs.' + this.scope + '.selectDefaultFilters.boolFilterList');

        if (boolFilterList) {
            let d = {};

            boolFilterList.forEach(item => {
                d[item] = true;
            });

            searchManager.setBool(d);
        }

        let primaryFilterName = this.primaryFilterName ||
            this.getMetadata().get('clientDefs.' + this.scope + '.selectDefaultFilters.filter');

        if (primaryFilterName) {
            searchManager.setPrimary(primaryFilterName);
        }

        this.collection.where = searchManager.getWhere();

        if (this.searchPanel) {
            this.createView('search', 'views/record/search', {
                collection: this.collection,
                fullSelector: this.containerSelector + ' .search-container',
                searchManager: searchManager,
                disableSavePreset: true,
                filterList: this.filterList,
            }, view => {
                this.listenTo(view, 'reset', () => {});
            });
        }
    }

    setupList() {
        const viewName = this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.listSelect') ||
            this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.list') ||
            'views/record/list';

        const promise = this.createView('list', viewName, {
            collection: this.collection,
            fullSelector: this.containerSelector + ' .list-container',
            selectable: true,
            checkboxes: this.multiple,
            massActionsDisabled: true,
            rowActionsView: false,
            layoutName: 'listSmall',
            searchManager: this.searchManager,
            checkAllResultDisabled: !this.massRelateEnabled,
            buttonsDisabled: true,
            skipBuildRows: true,
            pagination: this.getMetadata().get(['clientDefs', this.scope, 'listPagination']) || null,
        }, view => {

            this.listenToOnce(view, 'select', model => {
                this.trigger('select', model);

                this.close();
            });

            if (this.multiple) {
                this.listenTo(view, 'check', () => {
                    if (view.checkedList.length) {
                        this.enableButton('select');
                    }
                    else {
                        this.disableButton('select');
                    }
                });

                this.listenTo(view, 'select-all-results', () => {
                    this.enableButton('select');
                });
            }

            const fetch = () => {
                // Timeout to make notify work.
                setTimeout(() => {
                    Espo.Ui.notify(' ... ');

                    this.collection.fetch()
                        .then(() => {
                            Espo.Ui.notify(false);

                            this.$el.find('.bottom-button-container').removeClass('hidden');
                        });
                }, 1);
            };

            if (this.options.forceSelectAllAttributes || this.forceSelectAllAttributes) {
                fetch();

                return;
            }

            view.getSelectAttributeList(selectAttributeList => {
                if (!~selectAttributeList.indexOf('name')) {
                    selectAttributeList.push('name');
                }

                let mandatorySelectAttributeList = this.options.mandatorySelectAttributeList ||
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

    create() {
        if (this.options.triggerCreateEvent) {
            this.trigger('create');

            return;
        }

        Espo.Ui.notify(' ... ');

        let viewName = this.getMetadata()
            .get(['clientDefs', this.scope, 'modalViews', 'edit']) ||
            'views/modals/edit';

        new Promise(resolve => {
            if (this.options.createAttributesProvider) {
                this.options.createAttributesProvider().then(attributes => {
                    resolve(attributes)
                });

                return;
            }

            resolve(this.options.createAttributes || {});
        })
            .then(attributes => {
                this.createView('quickCreate', viewName, {
                    scope: this.scope,
                    fullFormDisabled: true,
                    attributes: attributes,
                }, view => {
                    view.render()
                        .then(() => Espo.Ui.notify(false));

                    this.listenToOnce(view, 'leave', () => {
                        view.close();
                        this.close();
                    });

                    this.listenToOnce(view, 'after:save', (model) => {
                        view.close();

                        this.trigger('select', model);

                        setTimeout(() => this.close(), 10);
                    });
                });
            });
    }

    actionSelect() {
        if (!this.multiple) {
            return;
        }

        let listView = this.getRecordView();

        if (listView.allResultIsChecked) {
            this.trigger('select', {
                massRelate: true,
                where: this.collection.getWhere(),
                searchParams: this.collection.data,
            });

            this.close();

            return;
        }

        let list = listView.getSelected();

        if (list.length) {
            this.trigger('select', list);
        }

        this.close();
    }

    /**
     * @protected
     * @return {?module:views/record/search}
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
     * @param {JQueryKeyEventObject} e
     */
    handleShortcutKeyCtrlSlash(e) {
        if (!this.searchPanel) {
            return;
        }

        let $search = this.$el.find('input.text-filter').first();

        if (!$search.length) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        $search.focus();
    }

    /**
     * @protected
     * @param {JQueryKeyEventObject} e
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
     * @param {JQueryKeyEventObject} e
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
