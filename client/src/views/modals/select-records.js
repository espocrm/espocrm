/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/modals/select-records', ['views/modal', 'search-manager'], function (Dep, SearchManager) {

    /**
     * A select-records modal.
     *
     * @class
     * @name Class
     * @memberOf module:views/modals/select-records
     * @extends module:views/modal.Class
     */
    return Dep.extend(/** @lends module:views/modals/select-records.Class# */{

        cssName: 'select-modal',

        multiple: false,

        header: false,

        template: 'modals/select-records',

        createButton: true,

        searchPanel: true,

        scope: null,

        noCreateScopeList: ['User', 'Team', 'Role', 'Portal'],

        className: 'dialog dialog-record',

        /**
         * @inheritDoc
         */
        shortcutKeys: {
            'Control+Enter': function (e) {
                this.handleShortcutKeyCtrlEnter(e);
            },
            'Control+Space': function (e) {
                this.handleShortcutKeyCtrlSpace(e);
            },
            'Control+Slash': function (e) {
                this.handleShortcutKeyCtrlSlash(e);
            },
            'Control+Comma': function (e) {
                this.handleShortcutKeyCtrlComma(e);
            },
            'Control+Period': function (e) {
                this.handleShortcutKeyCtrlPeriod(e);
            },
        },

        data: function () {
            return {
                createButton: this.createButton,
                createText: this.translate('Create ' + this.scope, 'labels', this.scope),
            };
        },

        events: {
            'click button[data-action="create"]': function () {
                this.create();
            },
            'click .list a': function (e) {
                e.preventDefault();
            }
        },

        setup: function () {
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
                }
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

            this.headerHtml = '';

            var iconHtml = this.getHelper().getScopeColorIconHtml(this.scope);

            this.headerHtml += this.translate('Select') + ': ';
            this.headerHtml += this.getLanguage().translate(this.scope, 'scopeNamesPlural');
            this.headerHtml = iconHtml + this.headerHtml;

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

                this.loadSearch();
                this.wait(true);
                this.loadList();
            });
        },

        loadSearch: function () {
            var searchManager = this.searchManager =
                new SearchManager(this.collection, 'listSelect', null, this.getDateTime());

            searchManager.emptyOnReset = true;

            if (this.filters) {
                searchManager.setAdvanced(this.filters);
            }

            var boolFilterList = this.boolFilterList ||
                this.getMetadata().get('clientDefs.' + this.scope + '.selectDefaultFilters.boolFilterList');

            if (boolFilterList) {
                var d = {};

                boolFilterList.forEach((item) => {
                    d[item] = true;
                });

                searchManager.setBool(d);
            }

            var primaryFilterName = this.primaryFilterName ||
                this.getMetadata().get('clientDefs.' + this.scope + '.selectDefaultFilters.filter');

            if (primaryFilterName) {
                searchManager.setPrimary(primaryFilterName);
            }

            this.collection.where = searchManager.getWhere();

            if (this.searchPanel) {
                this.createView('search', 'views/record/search', {
                    collection: this.collection,
                    el: this.containerSelector + ' .search-container',
                    searchManager: searchManager,
                    disableSavePreset: true,
                    filterList: this.filterList,
                }, (view) => {
                    this.listenTo(view, 'reset', () => {});
                });
            }
        },

        loadList: function () {
            var viewName = this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.listSelect') ||
                this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.list') ||
                'views/record/list';

            this.createView('list', viewName, {
                collection: this.collection,
                el: this.containerSelector + ' .list-container',
                selectable: true,
                checkboxes: this.multiple,
                massActionsDisabled: true,
                rowActionsView: false,
                layoutName: 'listSmall',
                searchManager: this.searchManager,
                checkAllResultDisabled: !this.massRelateEnabled,
                buttonsDisabled: true,
                skipBuildRows: true,
            }, function (view) {
                this.listenToOnce(view, 'select', (model) =>{
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

                if (this.options.forceSelectAllAttributes || this.forceSelectAllAttributes) {
                    this.listenToOnce(view, 'after:build-rows', () => {
                        this.wait(false);
                    });

                    this.collection.fetch();
                }
                else {
                    view.getSelectAttributeList((selectAttributeList) => {
                        if (!~selectAttributeList.indexOf('name')) {
                            selectAttributeList.push('name');
                        }

                        var mandatorySelectAttributeList = this.options.mandatorySelectAttributeList ||
                            this.mandatorySelectAttributeList || [];

                        mandatorySelectAttributeList.forEach((attribute) => {
                            if (!~selectAttributeList.indexOf(attribute)) {
                                selectAttributeList.push(attribute);
                            }
                        });

                        if (selectAttributeList) {
                            this.collection.data.select = selectAttributeList.join(',');
                        }

                        this.listenToOnce(view, 'after:build-rows', () => {
                            this.wait(false);
                        });

                        this.collection.fetch();
                    });
                }
            });
        },

        create: function () {
            if (this.options.triggerCreateEvent) {
                this.trigger('create');

                return;
            }

            this.notify('Loading...');

            this.createView('quickCreate', 'views/modals/edit', {
                scope: this.scope,
                fullFormDisabled: true,
                attributes: this.options.createAttributes,
            }, (view) => {
                view.once('after:render', () => {
                    this.notify(false);
                });

                view.render();

                this.listenToOnce(view, 'leave', () => {
                    view.close();
                    this.close();
                });

                this.listenToOnce(view, 'after:save', (model) => {
                    view.close();

                    this.trigger('select', model);

                    setTimeout(() => {
                        this.close();
                    }, 10);
                });
            });
        },

        actionSelect: function () {
            if (!this.multiple) {
                return;
            }

            let listView = this.getView('list');

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
        },

        /**
         * @protected
         * @return {?module:views/record/search.Class}
         */
        getSearchView: function () {
            return this.getView('search');
        },

        /**
         * @protected
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyCtrlSlash: function (e) {
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
        },

        /**
         * @protected
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyCtrlEnter: function (e) {
            if (!this.multiple) {
                return;
            }

            if (!this.hasAvailableActionItem('select')) {
                return;
            }

            e.stopPropagation();
            e.preventDefault();

            this.actionSelect();
        },

        /**
         * @protected
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyCtrlSpace: function (e) {
            if (!this.createButton) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            this.create();
        },

        /**
         * @protected
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyCtrlComma: function (e) {
            if (!this.getSearchView()) {
                return;
            }

            this.getSearchView().selectPreviousPreset();
        },

        /**
         * @protected
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyCtrlPeriod: function (e) {
            if (!this.getSearchView()) {
                return;
            }

            this.getSearchView().selectNextPreset();
        },
    });
});
