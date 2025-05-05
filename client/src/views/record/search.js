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

/** @module views/record/search */

import View from 'view';
import StoredTextSearch from 'helpers/misc/stored-text-search';
import Autocomplete from 'ui/autocomplete';

/**
 * @typedef {Object} module:views/record/search~boolFilterDefs
 * @property {boolean} [inPortalDisabled]
 * @property {boolean} [isPortalOnly]
 * @property {boolean} [aux]
 * @property {module:utils~AccessDefs[]} [accessDataList]
 */

/**
 * A search panel view.
 */
class SearchView extends View {

    template = 'record/search'

    scope = ''
    entityType = ''
    /** @type {module:search-manager} */
    searchManager
    fieldFilterList = null
    /** @type {Object.<string, string>|null}*/
    fieldFilterTranslations = null

    textFilter = ''
    /**
     * @type {string|null}
     */
    primary = null
    presetFilterList = null
    /** @type {{string: module:search-manager~advancedFilter}} */
    advanced
    bool = null
    filtersLayoutName = 'filters'

    disableSavePreset = false
    textFilterDisabled = false
    toShowApplyFiltersButton = false
    toShowResetFiltersText = false
    isSearchedWithAdvancedFilter = false
    primaryFiltersDisabled = false

    viewModeIconClassMap = {
        list: 'fas fa-align-justify',
        kanban: 'fas fa-align-left fa-rotate-90',
    }

    FIELD_QUICK_SEARCH_COUNT_THRESHOLD = 4

    autocompleteLimit = 7

    data() {
        return {
            scope: this.scope,
            entityType: this.entityType,
            textFilter: this.textFilter,
            bool: this.bool || {},
            boolFilterList: this.boolFilterList,
            hasFieldQuickSearch: this.fieldFilterList.length >= this.FIELD_QUICK_SEARCH_COUNT_THRESHOLD,
            filterFieldDataList: this.getFilterFieldDataList(),
            filterDataList: this.getFilterDataList(),
            presetName: this.presetName,
            presetFilterList: this.getPresetFilterList(),
            leftDropdown: this.hasLeftDropdown(),
            textFilterDisabled: this.textFilterDisabled,
            viewMode: this.viewMode,
            viewModeDataList: this.viewModeDataList || [],
            hasViewModeSwitcher: this.viewModeList && this.viewModeList.length > 1,
            isWide: this.options.isWide,
            toShowApplyFiltersButton: this.toShowApplyFiltersButton,
            toShowResetFiltersText: this.toShowResetFiltersText,
            primaryFiltersDisabled: this.primaryFiltersDisabled,
        };
    }

    setup() {
        this.entityType = this.collection.entityType;
        this.scope = this.options.scope || this.entityType;
        this.filtersLayoutName = this.options.filtersLayoutName || this.filtersLayoutName;
        this.primaryFiltersDisabled = this.options.primaryFiltersDisabled || this.primaryFiltersDisabled;

        this.viewModeIconClassMap = {
            ...this.viewModeIconClassMap,
            ...this.getMetadata().get(`clientDefs.${this.scope}.viewModeIconClassMap`),
        };

        /** @type {module:search-manager} */
        this.searchManager = this.options.searchManager;

        /** @private */
        this.storedTextSearchHelper = new StoredTextSearch(this.scope, this.getHelper().storage);

        this.textSearchStoringDisabled = this.getPreferences().get('textSearchStoringDisabled');

        this.textFilterDisabled = this.options.textFilterDisabled || this.textFilterDisabled ||
            this.getMetadata().get(['clientDefs', this.scope, 'textFilterDisabled']);

        if ('disableSavePreset' in this.options) {
            this.disableSavePreset = this.options.disableSavePreset;
        }

        this.viewMode = this.options.viewMode;
        this.viewModeList = this.options.viewModeList;

        this.addReadyCondition(() => {
            return this.fieldFilterList !== null;
        });

        this.boolFilterList = Espo.Utils
            .clone(this.getMetadata().get(['clientDefs', this.scope, 'boolFilterList']) || [])
            .filter(/** module:views/record/search~boolFilterDefs|string */item => {
                if (typeof item === 'string') {
                    return true;
                }

                item = item || {};

                if (item.aux) {
                    return false;
                }

                if (item.inPortalDisabled && this.getUser().isPortal()) {
                    return false;
                }

                if (item.isPortalOnly && !this.getUser().isPortal()) {
                    return false;
                }

                if (item.accessDataList) {
                    if (!Espo.Utils.checkAccessDataList(item.accessDataList, this.getAcl(), this.getUser())) {
                        return false;
                    }
                }

                return true;
            })
            .map(item => {
                if (typeof item === 'string') {
                    return item;
                }

                item = item || {};

                return item.name;
            });

        this.fieldFilterTranslations = {};

        const forbiddenFieldList = this.getAcl().getScopeForbiddenFieldList(this.entityType) || [];

        this.wait(
            new Promise(resolve => {
                this.getHelper().layoutManager.get(this.entityType, this.filtersLayoutName, list => {
                    this.fieldFilterList = [];

                    (list || []).forEach(field => {
                        if (~forbiddenFieldList.indexOf(field)) {
                            return;
                        }

                        this.fieldFilterList.push(field);
                        this.fieldFilterTranslations[field] = this.translate(field, 'fields', this.entityType);
                    });

                    resolve();
                });
            })
        );
        this.setupPresetFilters();

        if (this.getMetadata().get(['scopes', this.entityType, 'stream'])) {
            this.boolFilterList.push('followed');
        }

        if (this.getMetadata().get(`scopes.${this.entityType}.collaborators`) && !this.getUser().isPortal()) {
            this.boolFilterList.push('shared');
        }

        this.loadSearchData();

        if (this.hasAdvancedFilter()) {
            this.isSearchedWithAdvancedFilter = true;
        }

        if (this.presetName) {
            let hasPresetListed = false;

            for (const i in this.presetFilterList) {
                const item = this.presetFilterList[i] || {};

                const name = (typeof item === 'string') ? item : item.name;

                if (name === this.presetName) {
                    hasPresetListed = true;

                    break;
                }
            }

            if (!hasPresetListed) {
                this.presetFilterList.push(this.presetName);
            }
        }

        this.model = this.collection.prepareModel();

        this.model.clear();

        this.createFilters();
        this.setupViewModeDataList();

        this.listenTo(this.collection, 'order-changed', () => {
            this.controlResetButtonVisibility();
        });

        this.wait(
            this.getHelper().processSetupHandlers(this, 'record/search')
        );
    }

    setupPresetFilters() {
        if (this.primaryFiltersDisabled) {
            this.presetFilterList = [];

            return;
        }

        const filterList = this.options.filterList ||
            this.getMetadata().get(['clientDefs', this.scope, 'filterList']) || [];

        this.presetFilterList = filterList.filter(item => {
            if (typeof item === 'string') {
                return true;
            }

            item = item || {};

            if (item.aux) {
                return false;
            }

            if (item.inPortalDisabled && this.getUser().isPortal()) {
                return false;
            }

            if (item.isPortalOnly && !this.getUser().isPortal()) {
                return false;
            }

            if (item.accessDataList) {
                if (!Espo.Utils.checkAccessDataList(item.accessDataList, this.getAcl(), this.getUser())) {
                    return false;
                }
            }

            return true;
        });

        if (this.getMetadata().get(`scopes.${this.scope}.stars`)) {
            this.presetFilterList.unshift({
                name: 'starred',
            });
        }

        ((this.getPreferences().get('presetFilters') || {})[this.scope] || [])
            .forEach(item => {
                this.presetFilterList.push(item);
            });
    }

    setupViewModeDataList() {
        if (!this.viewModeList) {
            return [];
        }

        const list = [];

        this.viewModeList.forEach(item => {
            const o = {
                name: item,
                title: this.translate(item, 'listViewModes'),
                iconClass: this.viewModeIconClassMap[item]
            };

            list.push(o);
        });

        this.viewModeDataList = list;
    }

    setViewMode(mode, preventLoop, toTriggerEvent) {
        this.viewMode = mode;

        if (this.isRendered()) {
            this.$el.find('[data-action="switchViewMode"]').removeClass('active');
            this.$el.find('[data-action="switchViewMode"][data-name="'+mode+'"]').addClass('active');
        }
        else {
            if (this.isBeingRendered() && !preventLoop) {
                this.once('after:render', () => {
                    this.setViewMode(mode, true);
                });
            }
        }

        this.collection.offset = 0;

        if (toTriggerEvent) {
            this.trigger('change-view-mode', mode);
        }
    }

    hasLeftDropdown() {
        if (this.primaryFiltersDisabled && !this.boolFilterList.length) {
            return false;
        }

        return this.presetFilterList.length ||
            this.boolFilterList.length ||
            Object.keys(this.advanced || {}).length;
    }

    handleLeftDropdownVisibility() {
        if (this.hasLeftDropdown()) {
            this.$leftDropdown.removeClass('hidden');
        }
        else {
            this.$leftDropdown.addClass('hidden');
        }
    }

    createFilters(callback) {
        let i = 0;
        const count = Object.keys(this.advanced || {}).length;

        if (count === 0) {
            if (typeof callback === 'function') {
                callback();
            }
        }

        for (const field in this.advanced) {
            this.createFilter(field, this.advanced[field], () => {
                i++;

                if (i === count) {
                    if (typeof callback === 'function') {
                        callback();
                    }
                }
            });
        }
    }

    events = {
        /** @this SearchView */
        'keydown input[data-name="textFilter"]': function (e) {
            const key = Espo.Utils.getKeyFromKeyEvent(e);

            if (e.key === 'Enter' || key === 'Enter' || key === 'Control+Enter') {
                this.search();

                this.hideApplyFiltersButton();
            }
        },
        /** @this SearchView */
        'focus input[data-name="textFilter"]': function (e) {
            e.currentTarget.select();
        },
        /** @this SearchView */
        'click .advanced-filters-apply-container a[data-action="applyFilters"]': function () {
            this.search();
            this.hideApplyFiltersButton();

            this.$el.find('button.search').focus();
        },
        /** @this SearchView */
        'click button[data-action="search"]': function () {
            this.search();
            this.hideApplyFiltersButton();
        },
        /** @this SearchView */
        'click a[data-action="addFilter"]': function (e) {
            const $target = $(e.currentTarget);
            const name = $target.data('name');

            $target.closest('li').addClass('hidden');

            this.addFilter(name);
        },
        /** @this SearchView */
        'click .advanced-filters a.remove-filter': function (e) {
            const $target = $(e.currentTarget);

            const name = $target.data('name');

            this.removeFilter(name);
        },
        /** @this SearchView */
        'click button[data-action="reset"]': function () {
            this.resetFilters();
        },
        /** @this SearchView */
        'click button[data-action="refresh"]': function () {
            this.refresh();
        },
        /** @this SearchView */
        'click a[data-action="selectPreset"]': function (e) {
            const $target = $(e.currentTarget);

            const presetName = $target.data('name') || null;

            this.selectPreset(presetName);
        },
        /** @this SearchView */
        'click .dropdown-menu a[data-action="savePreset"]': function () {
            this.createView('savePreset', 'views/modals/save-filters', {}, view => {
                view.render();

                this.listenToOnce(view, 'save', (name) => {
                    this.savePreset(name);

                    view.close();

                    this.removeFilters();

                    this.createFilters(() => {
                        this.render();
                    });
                });
            });
        },
        /** @this SearchView */
        'click .dropdown-menu a[data-action="removePreset"]': function () {
            const id = this.presetName;

            this.confirm(this.translate('confirmation', 'messages'), () => {
                this.removePreset(id);
            });
        },
        /** @this SearchView */
        'change .search-row ul.filter-menu input[data-role="boolFilterCheckbox"]': function (e) {
            e.stopPropagation();

            this.search();
            this.manageLabels();
        },
        /** @this SearchView */
        'click [data-action="switchViewMode"]': function (e) {
            const mode = $(e.currentTarget).data('name');

            if (mode === this.viewMode) {
                return;
            }

            this.setViewMode(mode, false, true);
        },
        /** @this SearchView */
        'keyup input.field-filter-quick-search-input': function (e) {
            this.processFieldFilterQuickSearch(e.currentTarget.value);
        },
        /** @this SearchView */
        'keydown input.field-filter-quick-search-input': function (e) {
            if (e.code === 'Enter') {
                this.addFirstFieldFilter();

                return;
            }

            if (e.code === 'Escape') {
                this.closeAddFieldDropdown();
            }
        },
    }

    removeFilter(name) {
        this.$el.find('ul.filter-list li[data-name="' + name + '"]').removeClass('hidden');

        const container = this.getView('filter-' + name).$el.closest('div.filter');

        this.clearView('filter-' + name);

        container.remove();

        delete this.advanced[name];

        this.presetName = this.primary;

        this.updateAddFilterButton();
        this.fetch();
        this.updateSearch();
        this.manageLabels();
        this.handleLeftDropdownVisibility();
        this.controlResetButtonVisibility();

        if (this.isSearchedWithAdvancedFilter) {
            this.hasAdvancedFilter() ?
                this.showApplyFiltersButton() :
                this.showResetFiltersButton();

            this.$applyFilters.focus();

            return;
        }

        if (!this.hasAdvancedFilter()) {
            this.hideApplyFiltersButton();
        }
    }

    addFilter(name) {
        this.advanced[name] = {};

        this.presetName = this.primary;

        this.createFilter(name, {}, view => {
            view.populateDefaults();

            this.fetch();
            this.updateSearch();

            if (view.getFieldView().initialSearchIsNotIdle) {
                this.showApplyFiltersButton();
            }
        });

        this.updateAddFilterButton();
        this.handleLeftDropdownVisibility();

        this.manageLabels();
        this.controlResetButtonVisibility();
    }

    refresh() {
        Espo.Ui.notifyWait();

        this.collection.abortLastFetch();
        this.collection.reset();

        this.collection.fetch().then(() => {
            Espo.Ui.notify(false);
        });
    }

    selectPreset(presetName, forceClearAdvancedFilters) {
        const wasPreset = !(this.primary === this.presetName);

        this.presetName = presetName;

        const advanced = this.getPresetData();

        this.primary = this.getPrimaryFilterName();

        const isPreset = !(this.primary === this.presetName);

        if (forceClearAdvancedFilters || wasPreset || isPreset || Object.keys(advanced).length) {
            this.removeFilters();
            this.advanced = advanced;
        }

        this.updateSearch();
        this.manageLabels();

        this.createFilters(() => {
            this.reRender()
                .then(() => {
                    // noinspection JSUnresolvedReference
                    this.$el.find('.filters-button')
                        .get(0)
                        .focus({preventScroll: true});
                })
        });

        this.updateCollection();
    }

    removeFilters() {
        this.$advancedFiltersPanel.empty();

        for (const name in this.advanced) {
            this.clearView('filter-' + name);
        }
    }

    resetFilters() {
        this.trigger('reset');

        this.collection.resetOrderToDefault();

        this.textFilter = '';

        this.selectPreset(this.presetName, true);
        this.hideApplyFiltersButton();
        this.trigger('update-ui');
    }

    savePreset(name) {
        const id = 'f' + (Math.floor(Math.random() * 1000001)).toString();

        this.fetch();
        this.updateSearch();

        const presetFilters = this.getPreferences().get('presetFilters') || {};

        if (!(this.scope in presetFilters)) {
            presetFilters[this.scope] = [];
        }

        const data = {
            id: id,
            name: id,
            label: name,
            data: this.advanced,
            primary: this.primary,
        };

        presetFilters[this.scope].push(data);

        this.presetFilterList.push(data);

        this.getPreferences().once('sync', () => {
            this.getPreferences().trigger('update');
            this.updateSearch()
        });

        this.getPreferences().save({'presetFilters': presetFilters}, {patch: true});

        this.presetName = id;
    }

    removePreset(id) {
        const presetFilters = this.getPreferences().get('presetFilters') || {};

        if (!(this.scope in presetFilters)) {
            presetFilters[this.scope] = [];
        }

        let list;

        list = presetFilters[this.scope];

        list.forEach((item, i) => {
            if (item.id === id) {
                list.splice(i, 1);
            }
        });

        list = this.presetFilterList;

        list.forEach((item, i) => {
            if (item.id === id) {
                list.splice(i, 1);
            }
        });

        this.getPreferences().set('presetFilters', presetFilters);
        this.getPreferences().save({patch: true});
        this.getPreferences().trigger('update');

        this.presetName = this.primary;
        this.advanced = {};

        this.removeFilters();

        this.render();
        this.updateSearch();
        this.updateCollection();
    }

    updateAddFilterButton() {
        const $ul = this.$el.find('ul.filter-list');

        if (
            $ul.children()
                .not('.hidden')
                .not('.dropdown-header')
                .not('.quick-search-list-item').length === 0
        ) {
            this.$addFilterButton.addClass('disabled');
        }
        else {
            this.$addFilterButton.removeClass('disabled');
        }

        this.trigger('update-ui');
    }

    afterRender() {
        this.$filtersLabel = this.$el.find('.search-row span.filters-label');
        this.$filtersButton = this.$el.find('.search-row button.filters-button');
        this.$leftDropdown = this.$el.find('div.search-row div.left-dropdown');
        this.$resetButton = this.$el.find('[data-action="reset"]');
        this.$applyFiltersContainer = this.$el.find('.advanced-filters-apply-container');
        this.$applyFilters = this.$applyFiltersContainer.find('[data-action="applyFilters"]');
        /** @type {JQuery} */
        this.$filterList = this.$el.find('ul.filter-list');
        /** @type {JQuery} */
        this.$fieldQuickSearch = this.$filterList.find('input.field-filter-quick-search-input');
        /** @type {JQuery} */
        this.$addFilterButton = this.$el.find('button.add-filter-button');
        /** @type {JQuery} */
        this.$textFilter = this.$el.find('input.text-filter');

        this.updateAddFilterButton();

        this.$advancedFiltersPanel = this.$el.find('.advanced-filters');

        this.manageLabels();
        this.controlResetButtonVisibility();
        this.initQuickSearchUi();
        this.initTextSearchAutocomplete();
    }

    initTextSearchAutocomplete() {
        if (this.textSearchStoringDisabled) {
            return;
        }

        const autocomplete = new Autocomplete(this.$textFilter.get(0), {
            triggerSelectOnValidInput: false,
            focusOnSelect: true,
            onSelect: () => {
                setTimeout(() => autocomplete.hide(), 1);
            },
            lookupFunction: query => {
                return Promise.resolve(
                    this.storedTextSearchHelper.match(query, this.autocompleteLimit)
                        .map(item => ({value: item}))
                );
            },
            formatResult: item => {
                return $('<span>')
                    .append(
                        $('<a>')
                            .attr('data-action', 'clearStoredTextSearch')
                            .attr('role', 'button')
                            .attr('data-value', item.value)
                            .attr('title', this.translate('Remove'))
                            .html('<span class="fas fa-times fa-sm"></span>')
                            .addClass('pull-right text-soft'),
                        $('<span>')
                            .text(item.value)
                    )
                    .get(0).innerHTML;
            },
            beforeRender: container => {
                const $container = $(container);
                $container.addClass('text-search-suggestions');

                $container.find('a[data-action="clearStoredTextSearch"]').on('click', e => {
                    e.stopPropagation();
                    e.preventDefault();

                    const text = e.currentTarget.getAttribute('data-value');
                    this.storedTextSearchHelper.remove(text);

                    autocomplete.hide();
                    // 200 is hardcoded in autocomplete lib.
                    setTimeout(() => this.$textFilter.focus(), 201);
                });
            },
        });

        this.once('render remove', () => autocomplete.dispose());
    }

    initQuickSearchUi() {
        this.$addFilterButton.parent().on('show.bs.dropdown', () => {
            setTimeout(() => {
                this.$fieldQuickSearch.focus();

                const width = this.$fieldQuickSearch.outerWidth();

                this.$fieldQuickSearch.css('minWidth', width);
            }, 1);
        });

        this.$addFilterButton.parent().on('hide.bs.dropdown', () => {
            this.resetFieldFilterQuickSearch();

            this.$fieldQuickSearch.css('minWidth', '');
        });
    }

    manageLabels() {
        this.$el.find('ul.dropdown-menu > li.preset-control').addClass('hidden');

        this.currentFilterLabelList = [];

        this.managePresetFilters();
        this.manageBoolFilters();

        this.$filtersLabel.html(this.currentFilterLabelList.join(' &middot; '));
    }

    /**
     * @private
     * @return {boolean}
     */
    toShowResetButton() {
        if (this.textFilter) {
            return true;
        }

        const presetName = this.presetName || null;
        const primary = this.primary;

        if (!presetName || presetName === primary) {
            if (Object.keys(this.advanced).length) {
                return true;
            }
        }

        if (
            this.collection.orderBy !== this.collection.defaultOrderBy ||
            this.collection.order !== this.collection.defaultOrder
        ) {
            return true;
        }

        return false;
    }

    controlResetButtonVisibility() {
        if (this.toShowResetButton()) {
            this.$resetButton.css('visibility', 'visible');

            return;
        }

        this.$resetButton.css('visibility', 'hidden');
    }

    managePresetFilters() {
        let presetName = this.presetName || null;
        const primary = this.primary;

        this.$el.find('ul.filter-menu a.preset span').remove();

        let filterLabel = this.translate('all', 'presetFilters', this.entityType);
        let filterStyle = 'default';

        if (!presetName && primary) {
            presetName = primary;
        }

        if (presetName && presetName !== primary) {
            this.$advancedFiltersPanel.addClass('hidden');

            let label = null;
            let style = 'default';
            let id = null;

            this.presetFilterList.forEach(item => {
                if (item.name === presetName) {
                    label = item.label || false;
                    style = item.style || 'default';
                    id = item.id;
                }
            });

            label = label || this.translate(this.presetName, 'presetFilters', this.entityType);

            filterLabel = label;
            filterStyle = style;

            if (id) {
                this.$el.find('ul.dropdown-menu > li.divider.preset-control').removeClass('hidden');
                this.$el.find('ul.dropdown-menu > li.preset-control.remove-preset').removeClass('hidden');
            }
        }
        else {
            this.$advancedFiltersPanel.removeClass('hidden');

            if (Object.keys(this.advanced).length !== 0) {
                if (!this.disableSavePreset) {
                    this.$el.find('ul.dropdown-menu > li.divider.preset-control').removeClass('hidden');
                    this.$el.find('ul.dropdown-menu > li.preset-control.save-preset').removeClass('hidden');
                    this.$el.find('ul.dropdown-menu > li.preset-control.remove-preset').addClass('hidden');
                }
            }

            if (primary) {
                const label = this.translate(primary, 'presetFilters', this.entityType);
                const style = this.getPrimaryFilterStyle();

                filterLabel = label;
                filterStyle = style;
            }
        }

        this.currentFilterLabelList.push(filterLabel);

        this.$filtersButton
            .removeClass('btn-default')
            .removeClass('btn-primary')
            .removeClass('btn-danger')
            .removeClass('btn-success')
            .removeClass('btn-info');

        this.$filtersButton.addClass('btn-' + filterStyle);

        presetName = presetName || '';

        this.$el
            .find('ul.filter-menu a.preset[data-name="'+presetName+'"]')
            .prepend('<span class="fas fa-check pull-right"></span>');
    }

    manageBoolFilters() {
        (this.boolFilterList || []).forEach((item) => {
            if (this.bool[item]) {
                const label = this.translate(item, 'boolFilters', this.entityType);

                this.currentFilterLabelList.push(label);
            }
        });
    }

    search() {
        this.fetch();
        this.updateSearch();
        this.updateCollection();
        this.controlResetButtonVisibility();
        this.storeTextSearch();

        this.isSearchedWithAdvancedFilter = this.hasAdvancedFilter();
    }

    hasAdvancedFilter() {
        return Object.keys(this.advanced).length > 0;
    }

    getFilterDataList() {
        const list = [];

        for (const field in this.advanced) {
            list.push({
                key: 'filter-' + field,
                name: field,
            });
        }

        return list;
    }

    updateCollection() {
        this.collection.abortLastFetch();
        this.collection.reset();
        this.collection.where = this.searchManager.getWhere();
        this.collection.offset = 0;

        Espo.Ui.notifyWait();

        this.collection.fetch().then(() => {
            Espo.Ui.notify(false);
        });
    }

    getPresetFilterList() {
        const arr = [];

        this.presetFilterList.forEach((item) => {
            if (typeof item == 'string') {
                item = {name: item};
            }

            arr.push(item);
        });

        return arr;
    }

    getPresetData() {
        let data = {};

        this.getPresetFilterList().forEach(item => {
            if (item.name === this.presetName) {
                data = Espo.Utils.clone(item.data || {});
            }
        });

        return data;
    }

    getPrimaryFilterName() {
        let primaryFilterName = null;

        this.getPresetFilterList().forEach(item => {
            if (item.name === this.presetName) {
                if (!('data' in item)) {
                    primaryFilterName = item.name;
                }
                else if (item.primary) {
                    primaryFilterName = item.primary;
                }
            }
        });

        return primaryFilterName;
    }

    getPrimaryFilterStyle() {
        let style = null;

        this.getPresetFilterList().forEach(item => {
            if (item.name === this.primary) {
                style = item.style || 'default';
            }
        });

        return style;
    }

    loadSearchData() {
        const searchData = this.searchManager.get();

        this.textFilter = searchData.textFilter;

        if ('presetName' in searchData) {
            this.presetName = searchData.presetName;
        }

        let primaryIsSet = false;

        if ('primary' in searchData) {
            this.primary = searchData.primary;

            if (!this.presetName) {
                this.presetName = this.primary;
            }

            primaryIsSet = true;
        }

        if (this.presetName) {
            this.advanced = _.extend(Espo.Utils.clone(this.getPresetData()), searchData.advanced);

            if (!primaryIsSet) {
                this.primary = this.getPrimaryFilterName();
            }
        }
        else {
            this.advanced = Espo.Utils.clone(searchData.advanced);
        }

        this.bool = searchData.bool;
    }

    /**
     * @callback SearchView~createFilterCallback
     * @param {module:views/search/filter} view
     */

    /**
     * @param {string} name
     * @param {Object.<string, *>} params
     * @param {SearchView~createFilterCallback} callback
     * @param {boolean} [noRender]
     */
    createFilter(name, params, callback, noRender) {
        params = params || {};

        let rendered = false;

        if (this.isRendered()) {
            rendered = true;

            this.$advancedFiltersPanel.append(
                '<div data-name="'+name+'" class="filter filter-' + name + '" />'
            );
        }

        this.createView('filter-' + name, 'views/search/filter', {
            name: name,
            model: this.model,
            params: params,
            selector: '.filter[data-name="' + name + '"]',
        }, view => {
            if (typeof callback === 'function') {
                view.once('after:render', () => {
                    callback(view);
                });
            }

            if (rendered && !noRender) {
                view.render();
            }

            this.listenTo(view, 'change', () => {
                let toShowApply = this.isSearchedWithAdvancedFilter;

                if (!toShowApply) {
                    const data = view.getView('field').fetchSearch();

                    if (data) {
                        toShowApply = true;
                    }
                }

                if (!toShowApply) {
                    return;
                }

                this.showApplyFiltersButton();
            });

            this.listenTo(view, 'search', () => {
                this.search();
                this.hideApplyFiltersButton();
            });
        });
    }

    fetch() {
        this.textFilter = (this.$el.find('input[data-name="textFilter"]').val() || '').trim();

        this.bool = {};

        this.boolFilterList.forEach(name => {
            this.bool[name] = this.$el
                .find('input[data-name="' + name + '"][data-role="boolFilterCheckbox"]')
                .prop('checked');
        });

        for (const field in this.advanced) {
            const view = /** @type {module:views/fields/base} */
                this.getView('filter-' + field).getView('field');

            this.advanced[field] = view.fetchSearch();

            view.searchParams = Espo.Utils.clone(this.advanced[field] || {});
        }
    }

    updateSearch() {
        this.searchManager.set({
            textFilter: this.textFilter,
            advanced: this.advanced,
            bool: this.bool,
            presetName: this.presetName,
            primary: this.primary,
        });
    }

    getFilterFieldDataList() {
        const defs = [];

        for (const i in this.fieldFilterList) {
            const field = this.fieldFilterList[i];

            const o = {
                name: field,
                checked: (field in this.advanced),
                label: this.fieldFilterTranslations[field] || field,
            };

            defs.push(o);
        }

        return defs;
    }

    showResetFiltersButton() {
        this.toShowApplyFiltersButton = true;
        this.toShowResetFiltersText = true;

        this.$applyFiltersContainer.removeClass('hidden');

        this.$applyFiltersContainer.find('.text-apply').addClass('hidden');
        this.$applyFiltersContainer.find('.text-reset').removeClass('hidden');
    }

    showApplyFiltersButton() {
        this.toShowApplyFiltersButton = true;
        this.toShowResetFiltersText = false;

        this.$applyFiltersContainer.removeClass('hidden');

        this.$applyFiltersContainer.find('.text-reset').addClass('hidden');
        this.$applyFiltersContainer.find('.text-apply').removeClass('hidden');
    }

    hideApplyFiltersButton() {
        this.toShowApplyFiltersButton = false;
        this.toShowResetFiltersText = false;

        this.$applyFiltersContainer.addClass('hidden');
    }

    selectPreviousPreset() {
        const list = Espo.Utils.clone(this.getPresetFilterList());

        list.unshift({name: null});

        if (list.length === 1) {
            return;
        }

        const index = list.findIndex(item => item.name === this.presetName) - 1;

        if (index < 0) {
            return;
        }

        const preset = list[index];

        this.selectPreset(preset.name);
    }

    selectNextPreset() {
        const list = Espo.Utils.clone(this.getPresetFilterList());

        list.unshift({name: null});

        if (list.length === 1) {
            return;
        }

        const index = list.findIndex(item => item.name === this.presetName) + 1;

        if (index >= list.length) {
            return;
        }

        const preset = list[index];

        this.selectPreset(preset.name);
    }

    /**
     * @private
     * @param {string} text
     */
    processFieldFilterQuickSearch(text) {
        text = text.trim();
        text = text.toLowerCase();

        /** @type {JQuery} */
        const $li = this.$filterList.find('li.filter-item');

        if (text === '') {
            $li.removeClass('search-hidden');

            return;
        }

        $li.addClass('search-hidden');

        this.fieldFilterList.forEach(field => {
            let label = this.fieldFilterTranslations[field] || field;
            label = label.toLowerCase();

            const wordList = label.split(' ');

            let matched = label.indexOf(text) === 0;

            if (!matched) {
                matched = wordList
                    .filter(word => word.length > 3 && word.indexOf(text) === 0)
                    .length > 0;
            }

            if (matched) {
                $li.filter(`[data-name="${field}"]`).removeClass('search-hidden');
            }
        });
    }

    resetFieldFilterQuickSearch() {
        this.$fieldQuickSearch.val('');
        this.$filterList.find('li.filter-item').removeClass('search-hidden');
    }

    addFirstFieldFilter() {
        const $first = this.$filterList.find('li.filter-item:not(.hidden):not(.search-hidden)').first();

        if (!$first.length) {
            return;
        }

        const name = $first.attr('data-name');

        $first.addClass('hidden');

        this.closeAddFieldDropdown();
        this.addFilter(name);
        this.resetFieldFilterQuickSearch();
    }

    closeAddFieldDropdown() {
        // noinspection JSUnresolvedReference
        this.$addFilterButton.parent()
            .find('[data-toggle="dropdown"]')
            .dropdown('toggle');
    }

    storeTextSearch() {
        if (!this.textFilter) {
            return;
        }

        if (this.textSearchStoringDisabled) {
            return;
        }

        this.storedTextSearchHelper.store(this.textFilter);
    }
}

export default SearchView;
