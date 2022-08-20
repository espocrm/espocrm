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

define('views/modals/related-list', ['views/modal', 'search-manager'], function (Dep, SearchManager) {

    /**
     * A related-list modal.
     *
     * @class
     * @name Class
     * @memberOf module:views/modals/related-list
     * @extends module:views/modal.Class
     */
    return Dep.extend(/** @lends module:views/modals/related-list.Class# */{

        template: 'modals/related-list',

        searchPanel: true,

        scope: null,

        noCreateScopeList: ['User', 'Team', 'Role', 'Portal'],

        className: 'dialog dialog-record',

        backdrop: true,

        fixedHeaderHeight: true,

        mandatorySelectAttributeList: null,

        /**
         * @inheritDoc
         */
        shortcutKeys: {
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

        events: {
            'click button[data-action="createRelated"]': function () {
                this.actionCreateRelated();
            },
            'click .action': function (e) {
                var $el = $(e.currentTarget);

                var action = $el.data('action');

                var method = 'action' + Espo.Utils.upperCaseFirst(action);

                var data = $el.data();

                if (typeof this[method] === 'function') {
                    this[method](data, e);

                    e.preventDefault();
                }
                else {
                    this.trigger('action', action, data, e);
                }
            }
        },

        setup: function () {
            this.primaryFilterName = this.options.primaryFilterName || null;

            this.buttonList = [
                {
                    name: 'cancel',
                    label: 'Close',
                }
            ];

            this.scope = this.options.scope || this.scope;

            this.defaultOrderBy = this.options.defaultOrderBy;
            this.defaultOrder = this.options.defaultOrder;

            this.panelName = this.options.panelName;
            this.link = this.options.link;

            this.defs = this.options.defs || {};

            this.filterList = this.options.filterList;
            this.filter = this.options.filter;
            this.layoutName = this.options.layoutName || 'listSmall';
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

                    let model = this.collection.get(m.id);

                    if (!model) {
                        return;
                    }

                    let attributes = {};

                    for (let name in m.attributes) {
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

            this.unlinkDisabled = this.unlinkDisabled || this.options.unlinkDisabled;

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

            let iconHtml = this.getHelper().getScopeColorIconHtml(this.scope);

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
                    this.getLanguage().translate(this.link, 'links', this.model.name)
                )
            );

            if (this.options.listViewUrl) {
                this.$header = $('<a>')
                    .attr('href', this.options.listViewUrl)
                    .append(this.$header);
            }

            this.$header.prepend(iconHtml);

            this.waitForView('list');

            if (this.searchPanel) {
                this.waitForView('search');
            }

            this.getCollectionFactory().create(this.scope, (collection) => {
                collection.maxSize = this.getConfig().get('recordsPerPage');
                collection.url = this.url;

                collection.setOrder(this.defaultOrderBy, this.defaultOrder, true);

                this.collection = collection;

                if (this.panelCollection) {
                    this.listenTo(collection, 'change', (model) => {
                        var panelModel = this.panelCollection.get(model.id);

                        if (panelModel) {
                            panelModel.set(model.attributes);
                        }
                    });
                }

                this.loadSearch();

                this.wait(true);

                this.loadList();
            });
        },

        setFilter: function (filter) {
            this.searchManager.setPrimary(filter);
        },

        /**
         * @protected
         * @return {?module:views/record/search.Class}
         */
        getSearchView: function () {
            return this.getView('search');
        },

        loadSearch: function () {
            var searchManager = this.searchManager =
                new SearchManager(this.collection, 'listSelect', null, this.getDateTime());

            searchManager.emptyOnReset = true;

            var primaryFilterName = this.primaryFilterName;

            if (primaryFilterName) {
                searchManager.setPrimary(primaryFilterName);
            }

            this.collection.where = searchManager.getWhere();

            var filterList = Espo.Utils.clone(this.getMetadata().get(['clientDefs', this.scope, 'filterList']) || []);

            if (this.filterList) {
                this.filterList.forEach((item1) => {
                    var isFound = false;

                    var name1 = item1.name || item1;

                    if (!name1 || name1 === 'all') {
                        return;
                    }

                    filterList.forEach((item2) => {
                        var name2 = item2.name || item2;

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
                    el: this.containerSelector + ' .search-container',
                    searchManager: searchManager,
                    disableSavePreset: true,
                    filterList: filterList,
                }, (view) => {
                    this.listenTo(view, 'reset', () => {});
                });
            }
        },

        loadList: function () {
            var viewName =
                this.listViewName ||
                this.getMetadata().get(['clientDefs', this.scope, 'recordViews', 'listRelated']) ||
                this.getMetadata().get(['clientDefs', this.scope, 'recordViews', 'list']) ||
                'views/record/list';

            this.createView('list', viewName, {
                collection: this.collection,
                el: this.containerSelector + ' .list-container',
                rowActionsView: this.rowActionsView,
                layoutName: this.layoutName,
                searchManager: this.searchManager,
                buttonsDisabled: true,
                skipBuildRows: true,
                model: this.model,
                unlinkMassAction: !this.massUnlinkDisabled,
                massActionRemoveDisabled: this.massActionRemoveDisabled,
                massActionMassUpdateDisabled: this.massActionMassUpdateDisabled,
                mandatorySelectAttributeList: this.mandatorySelectAttributeList,
                rowActionsOptions: {
                    unlinkDisabled: this.unlinkDisabled,
                },
                pagination: this.getConfig().get('listPagination') ||
                    this.getMetadata().get(['clientDefs', this.scope, 'listPagination']) ||
                    null,
            }, (view) => {
                this.listenToOnce(view, 'select', (model) => {
                    this.trigger('select', model);

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

        actionUnlinkRelated: function (data) {
            var id = data.id;

            this.confirm({
                message: this.translate('unlinkRecordConfirmation', 'messages'),
                confirmText: this.translate('Unlink'),
            }, () => {
                this.notify('Unlinking...');

                Espo.Ajax.deleteRequest(this.collection.url, {
                    id: id,
                }).then(() => {
                    this.notify('Unlinked', 'success');

                    this.collection.fetch();

                    this.model.trigger('after:unrelate');
                    this.model.trigger('after:unrelate:' + this.link);
                });
            });
        },

        actionCreateRelated: function () {
            var p = this.getParentView();

            var view = null;

            while (p) {
                if (p.actionCreateRelated) {
                    view = p;

                    break;
                }

                p = p.getParentView();
            }

            p.actionCreateRelated({
                link: this.link,
                scope: this.scope,
            });
        },

        actionSelectRelated: function () {
            var p = this.getParentView();

            var view = null;

            while (p) {
                if (p.actionSelectRelated) {
                    view = p;

                    break;
                }

                p = p.getParentView();
            }

            p.actionSelectRelated({
                link: this.link,
                primaryFilterName: this.defs.selectPrimaryFilterName,
                boolFilterList: this.defs.selectBoolFilterList,
                massSelect: this.defs.massSelect,
            });
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
        handleShortcutKeyCtrlSpace: function (e) {
            if (this.createDisabled) {
                return;
            }

            if (this.buttonList.findIndex(item => item.name === 'createRelated' && !item.hidden) === -1) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            this.actionCreateRelated();
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
