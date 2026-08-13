/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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
import StoredTextSearch from 'helpers/misc/stored-text-search';
import Autocomplete from 'ui/autocomplete';
import FilterView from 'views/search/filter';
import SearchManager, {AdvancedFilter} from 'search-manager';
import Utils from 'utils';
import Ui from 'ui';
import BaseFieldView from 'views/fields/base';
import SaveFiltersModalView from 'views/modals/save-filters';
import _ from 'underscore';
import type Model from 'model';
import type Collection from 'collection';

interface BoolFilterDefs {
    name?: string;
    inPortalDisabled?: boolean;
    isPortalOnly?: boolean;
    aux?: boolean;
    accessDataList?: import('utils').AccessDefs[];
}

interface PresetFilter {
    name: string | null;
    label?: string;
    primary?: boolean;
    data?: any;
    style?: string;
}

type CreateFilterCallback = (view: import('views/search/filter').default) => void;

interface SearchViewOptions {
    isWide?: boolean;
    scope?: string;
    filtersLayoutName?: string;
    primaryFiltersDisabled?: boolean;
    searchManager: SearchManager;
    textFilterDisabled?: boolean;
    disableSavePreset?: boolean;
    viewMode: string;
    viewModeList?: string[];
    filterList?: string[];
}

interface ViewSchema {
    model: Model;
    collection: Collection;
    options: SearchViewOptions;
}

/**
 * A search panel view.
 */
class SearchView extends View<ViewSchema> {

    constructor(options: SearchViewOptions & {collection: Collection}) {
        super(options);
    }

    protected template: string = 'record/search'

    private scope: string

    private entityType: string

    private searchManager: SearchManager

    private fieldFilterList: string[] | null = null

    private fieldFilterTranslations: Record<string, string>

    private textFilter: string = ''

    private presetName: string | null = null

    private primary: string | null = null

    private presetFilterList: (
        {
            name?: string,
            label?: string,
            id?: string,
            style?: string,
        } | string
    )[]

    private advanced: Record<string, AdvancedFilter | Partial<AdvancedFilter> | Record<string, any> | null>

    private bool: Record<string, boolean> | null = null

    private filtersLayoutName: string = 'filters'

    private disableSavePreset: boolean = false

    private textFilterDisabled: boolean = false

    private toShowApplyFiltersButton: boolean = false

    private toShowResetFiltersText: boolean = false

    private isSearchedWithAdvancedFilter: boolean = false

    private primaryFiltersDisabled: boolean = false

    private viewModeIconClassMap: Record<string, string> = {
        list: 'fas fa-align-justify',
        kanban: 'fas fa-align-left fa-rotate-90',
    }

    readonly FIELD_QUICK_SEARCH_COUNT_THRESHOLD = 4

    private autocompleteLimit: number = 7

    private boolFilterList: string[]

    private viewMode: string

    private viewModeList: string[] | null

    private viewModeDataList: {
        name: string,
        title: string,
        iconClass?: string,
    }[]

    storedTextSearchHelper: StoredTextSearch

    private textSearchStoringDisabled: boolean;

    private currentFilterLabelList: string[] | null = null

    private filtersLabel: HTMLElement | null;

    private applyFiltersContainer: HTMLElement;

    private applyFiltersElement: HTMLElement;

    private filtersButton: HTMLButtonElement;

    private leftDropdownElement: HTMLButtonElement | null;

    private resetButtonElement: HTMLButtonElement | null;

    private addFilterButtonElement: HTMLButtonElement | null;

    private filterListElement: HTMLUListElement | null;

    private fieldQuickSearchInput: HTMLInputElement | null;

    private textFilterInputElement: HTMLInputElement | null;

    private advancedFiltersPanelElement: HTMLElement;

    protected data(): Record<string, any> {
        return {
            scope: this.scope,
            entityType: this.entityType,
            textFilter: this.textFilter,
            bool: this.bool || {},
            boolFilterList: this.boolFilterList,
            hasFieldQuickSearch: this.fieldFilterList!.length >= this.FIELD_QUICK_SEARCH_COUNT_THRESHOLD,
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

    protected setup() {
        this.setupEventHandlers();

        if (!this.collection.entityType) {
            throw new Error("No entity type.");
        }

        this.entityType = this.collection.entityType;
        this.scope = this.options.scope || this.entityType;
        this.filtersLayoutName = this.options.filtersLayoutName || this.filtersLayoutName;
        this.primaryFiltersDisabled = this.options.primaryFiltersDisabled || this.primaryFiltersDisabled;

        this.viewModeIconClassMap = {
            ...this.viewModeIconClassMap,
            ...this.getMetadata().get(`clientDefs.${this.scope}.viewModeIconClassMap`),
        };

        this.searchManager = this.options.searchManager;

        this.storedTextSearchHelper = new StoredTextSearch(this.scope, this.getHelper().storage);

        this.textSearchStoringDisabled = this.getPreferences().get('textSearchStoringDisabled');

        this.textFilterDisabled = this.options.textFilterDisabled || this.textFilterDisabled ||
            this.getMetadata().get(['clientDefs', this.scope, 'textFilterDisabled']);

        if ('disableSavePreset' in this.options) {
            this.disableSavePreset = this.options.disableSavePreset as boolean;
        }

        this.viewMode = this.options.viewMode;
        this.viewModeList = this.options.viewModeList ?? null;

        this.addReadyCondition(() => {
            return this.fieldFilterList !== null;
        });

        const boolFilterList = (this.getMetadata().get(['clientDefs', this.scope, 'boolFilterList']) ?? []) as
            (BoolFilterDefs | string)[];

        this.boolFilterList = boolFilterList
            .filter(item => {
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
                    if (!Utils.checkAccessDataList(item.accessDataList, this.getAcl(), this.getUser())) {
                        return false;
                    }
                }

                return true;
            })
            .map(item => {
                if (typeof item === 'string') {
                    return item;
                }

                item = item ?? {};

                return item.name;
            })
            .filter(it => it != null)

        this.fieldFilterTranslations = {};

        const forbiddenFieldList = this.getAcl().getScopeForbiddenFieldList(this.entityType) || [];

        this.wait(
            new Promise(resolve => {
                this.getHelper().layoutManager.get(this.entityType, this.filtersLayoutName, (list: string[]) => {
                    this.fieldFilterList = [];

                    (list ?? []).forEach(field => {
                        if (forbiddenFieldList.includes(field)) {
                            return;
                        }

                        this.fieldFilterList!.push(field);
                        this.fieldFilterTranslations[field] = this.translate(field, 'fields', this.entityType);
                    });

                    resolve(undefined);
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

    private setupEventHandlers() {
        this.addHandler('keydown', 'input[data-name="textFilter"]', (e) => {
            if (!(e instanceof KeyboardEvent)) {
                throw new Error();
            }

            const key = Utils.getKeyFromKeyEvent(e);

            if (e.key === 'Enter' || key === 'Enter' || key === 'Control+Enter') {
                this.search();

                this.hideApplyFiltersButton();
            }
        });

        this.addHandler('focus', 'input[data-name="textFilter"]', (_, target) => {
            (target as HTMLInputElement).select();
        });

        this.addHandler('click', '.advanced-filters-apply-container a[data-action="applyFilters"]', () => {
            this.search();
            this.hideApplyFiltersButton();

            this.element.querySelector<HTMLButtonElement>('button.search')?.focus();
        });

        this.addHandler('click', 'button[data-action="search"]', () => {
            this.search();
            this.hideApplyFiltersButton();
        });

        this.addHandler('click', 'a[data-action="addFilter"]', (_, target) => {
            const name = target.dataset.name as string;

            target?.closest('li')?.classList.add('hidden');

            this.addFilter(name);
        });

        this.addHandler('click', '.advanced-filters a.remove-filter', (_, target) => {
            const name = target.dataset.name as string;

            this.removeFilter(name);
        });

        this.addActionHandler('reset', () => this.resetFilters());
        this.addActionHandler('refresh', () => this.refresh());

        this.addActionHandler('selectPreset', (_, target) => {
            const presetName = target.dataset.name || null;

            this.selectPreset(presetName);
        });

        this.addHandler('click', '.dropdown-menu a[data-action="savePreset"]', () => this.savePresetHandler())

        this.addHandler('click', '.dropdown-menu a[data-action="removePreset"]', async () => {
            await this.confirm({message: this.translate('confirmation', 'messages')});

            this.removePreset(this.presetName!);
        });

        this.addHandler('change', '.search-row ul.filter-menu input[data-role="boolFilterCheckbox"]', (e) => {
            e.stopPropagation();

            this.search();
            this.manageLabels();
        });

        this.addActionHandler('switchViewMode', (_, target) => {
            const mode = target.dataset.name as string;

            if (mode === this.viewMode) {
                return;
            }

            this._setViewMode(mode, false, true);
        });

        this.addHandler('keyup', 'input.field-filter-quick-search-input', (_, target) => {
            this.processFieldFilterQuickSearch((target as HTMLInputElement).value);
        });

        this.addHandler('keydown', 'input.field-filter-quick-search-input', (e) => {
            if (!(e instanceof  KeyboardEvent)) {
                return;
            }

            if (e.code === 'Enter') {
                this.addFirstFieldFilter();

                return;
            }

            if (e.code === 'Escape') {
                this.closeAddFieldDropdown();
            }
        });
    }

    private setupPresetFilters() {
        if (this.primaryFiltersDisabled) {
            this.presetFilterList = [];

            return;
        }

        const filterList = (this.options.filterList ||
            this.getMetadata().get(['clientDefs', this.scope, 'filterList']) || []) as (string | Record<string, any>)[];

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
                if (!Utils.checkAccessDataList(item.accessDataList, this.getAcl(), this.getUser())) {
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

        const presets = ((this.getPreferences().get('presetFilters') ?? {})[this.scope] ?? []) as string[];

        presets.forEach(item => {
            this.presetFilterList.push(item);
        });
    }

    private setupViewModeDataList() {
        if (!this.viewModeList) {
            return [];
        }

        const list: any = [];

        this.viewModeList.forEach(item => {
            const o = {
                name: item,
                title: this.translate(item, 'listViewModes'),
                iconClass: this.viewModeIconClassMap[item] ?? null,
            };

            list.push(o);
        });

        this.viewModeDataList = list;
    }

    /**
     * Set a view mode.
     */
    setViewMode(mode: string) {
        this._setViewMode(mode);
    }

    private _setViewMode(mode: string, preventLoop?: boolean, toTriggerEvent?: boolean) {
        this.viewMode = mode;

        if (this.isRendered()) {
            this.$el.find('[data-action="switchViewMode"]').removeClass('active');
            this.$el.find(`[data-action="switchViewMode"][data-name="${mode}"]`).addClass('active');
        } else if (this.isBeingRendered() && !preventLoop) {
            this.once('after:render', () => {
                this._setViewMode(mode, true);
            });
        }

        this.collection.offset = 0;

        if (toTriggerEvent) {
            this.trigger('change-view-mode', mode);
        }
    }

    private hasLeftDropdown(): boolean {
        if (this.primaryFiltersDisabled && !this.boolFilterList.length) {
            return false;
        }

        return !!(
            this.presetFilterList.length ||
            this.boolFilterList.length ||
            Object.keys(this.advanced || {}).length
        );
    }

    private handleLeftDropdownVisibility() {
        if (this.hasLeftDropdown()) {
            this.leftDropdownElement?.classList.remove('hidden');
        } else {
            this.leftDropdownElement?.classList.add('hidden');
        }
    }

    private createFilters(callback?: () => void) {
        let i = 0;
        const count = Object.keys(this.advanced || {}).length;

        if (count === 0) {
            if (typeof callback === 'function') {
                callback();
            }
        }

        for (const field in this.advanced) {
            this.createFilter(field, this.advanced[field]!, () => {
                i++;

                if (i === count) {
                    if (typeof callback === 'function') {
                        callback();
                    }
                }
            });
        }
    }

    private removeFilter(name: string) {
        this.element.querySelector(`ul.filter-list li[data-name="${name}"]`)
            ?.classList.remove('hidden');

        //
        const container = this.getView(`filter-${name}`)?.element?.closest('div.filter');
            //.$el.closest('div.filter');

        this.clearView(`filter-${name}`);

        container?.remove();

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

            this.applyFiltersElement.focus();

            return;
        }

        if (!this.hasAdvancedFilter()) {
            this.hideApplyFiltersButton();
        }
    }

    private addFilter(name: string) {
        this.advanced[name] = {};

        this.presetName = this.primary;

        this.createFilter(name, {}, view => {
            view.populateDefaults();

            this.fetch();
            this.updateSearch();

            if (view.getFieldView()?.initialSearchIsNotIdle) {
                this.showApplyFiltersButton();
            }
        });

        this.updateAddFilterButton();
        this.handleLeftDropdownVisibility();

        this.manageLabels();
        this.controlResetButtonVisibility();
    }

    private refresh() {
        Ui.notifyWait();

        this.collection.abortLastFetch();
        this.collection.reset();

        this.collection.fetch().then(() => {
            Ui.notify(false);
        });
    }

    private selectPreset(presetName: string | null, forceClearAdvancedFilters?: boolean) {
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

        this.createFilters(async () => {
            await this.reRender();

            this.element.querySelector<HTMLElement>('.filters-button')
                ?.focus({preventScroll: true});
        });

        this.updateCollection();
    }

    private removeFilters() {
        this.advancedFiltersPanelElement.innerHTML = '';

        for (const name in this.advanced) {
            this.clearView('filter-' + name);
        }
    }

    private resetFilters() {
        this.trigger('reset');

        this.collection.resetOrderToDefault();

        this.textFilter = '';

        this.selectPreset(this.presetName, true);
        this.hideApplyFiltersButton();
        this.trigger('update-ui');
    }

    private savePreset(name: string) {
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

    private removePreset(id: string) {
        const presetFilters = this.getPreferences().get('presetFilters') || {};

        if (!(this.scope in presetFilters)) {
            presetFilters[this.scope] = [];
        }

        let list: any[];

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

    private updateAddFilterButton() {
        const noFields = !Array.from(this.filterListElement?.children ?? [])
            .some(li => {
                return !li.classList.contains('hidden') &&
                    !li.classList.contains('dropdown-header') &&
                    !li.classList.contains('quick-search-list-item');
            });

        if (noFields) {
            this.addFilterButtonElement?.classList.add('disabled');
        } else {
            this.addFilterButtonElement?.classList.remove('disabled');
        }

        this.trigger('update-ui');
    }

    protected afterRender() {
        this.filtersLabel = this.element.querySelector<HTMLElement>('.search-row span.filters-label');
        this.applyFiltersContainer = this.element.querySelector('.advanced-filters-apply-container')!;
        this.applyFiltersElement = this.applyFiltersContainer.querySelector('[data-action="applyFilters"]')!;
        this.filtersButton = this.element.querySelector<HTMLButtonElement>('.search-row button.filters-button')!;
        this.leftDropdownElement = this.element.querySelector('div.search-row div.left-dropdown');
        this.resetButtonElement = this.element.querySelector('[data-action="reset"]');
        this.filterListElement = this.element.querySelector<HTMLUListElement>('ul.filter-list');
        this.fieldQuickSearchInput = this.filterListElement
            ?.querySelector<HTMLInputElement>('input.field-filter-quick-search-input') ?? null;
        this.addFilterButtonElement = this.element.querySelector<HTMLButtonElement>('button.add-filter-button');
        this.textFilterInputElement = this.element.querySelector<HTMLInputElement>('input.text-filter');

        this.updateAddFilterButton();

        this.advancedFiltersPanelElement = this.element.querySelector<HTMLElement>('.advanced-filters')!;

        this.manageLabels();
        this.controlResetButtonVisibility();
        this.initQuickSearchUi();
        this.initTextSearchAutocomplete();
    }

    private initTextSearchAutocomplete() {
        if (this.textSearchStoringDisabled || !this.textFilterInputElement) {
            return;
        }

        const autocomplete = new Autocomplete(this.textFilterInputElement, {
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
                // @todo Refactor.
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
                    .get(0)!.innerHTML as string;
            },
            beforeRender: container => {
                // @todo Refactor.
                const $container = $(container);
                $container.addClass('text-search-suggestions');

                $container.find('a[data-action="clearStoredTextSearch"]').on('click', e => {
                    e.stopPropagation();
                    e.preventDefault();

                    const text = e.currentTarget.getAttribute('data-value');
                    this.storedTextSearchHelper?.remove(text as string);

                    autocomplete.hide();
                    // 200 is hardcoded in autocomplete lib.

                    setTimeout(() => this.textFilterInputElement?.focus(), 201);
                });
            },
        });

        this.once('render remove', () => autocomplete.dispose());
    }

    private initQuickSearchUi() {
        if (!this.addFilterButtonElement) {
            return;
        }

        const parent = this.addFilterButtonElement.parentElement;

        // @ts-ignore
        $(parent).on('show.bs.dropdown', () => {
            setTimeout(() => {
                if (!this.fieldQuickSearchInput) {
                    return;
                }

                this.fieldQuickSearchInput.focus();

                const width = this.fieldQuickSearchInput?.offsetWidth;

                this.fieldQuickSearchInput.style.minWidth = width.toString() + 'px';
            }, 1);
        });

        // @ts-ignore
        $(parent).on('hide.bs.dropdown', () => {
            this.resetFieldFilterQuickSearch();

            if (this.fieldQuickSearchInput) {
                this.fieldQuickSearchInput.style.minWidth = '';
            }
        });
    }

    private manageLabels() {
        this.element.querySelectorAll<HTMLLIElement>('ul.dropdown-menu > li.preset-control')
            .forEach(li => li.classList.add('hidden'))

        this.currentFilterLabelList = [];

        this.managePresetFilters();
        this.manageBoolFilters();

        if (!this.filtersLabel) {
            return;
        }

        this.filtersLabel.innerHTML = this.currentFilterLabelList
            .map(it => this.getHelper().escapeString(it))
            .join(' &middot; ');
    }

    private toShowResetButton(): boolean {
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

    private controlResetButtonVisibility() {
        if (!this.resetButtonElement) {
            return;
        }

        if (this.toShowResetButton()) {
            this.resetButtonElement.style.visibility = 'visible'

            return;
        }

        this.resetButtonElement.style.visibility = 'hidden';
    }

    private managePresetFilters() {
        let presetName = this.presetName || null;
        const primary = this.primary;

        this.$el.find('ul.filter-menu a.preset span').remove();

        let filterLabel = this.translate('all', 'presetFilters', this.entityType);
        let filterStyle: string | null = 'default';

        if (!presetName && primary) {
            presetName = primary;
        }

        if (presetName && presetName !== primary) {
            this.advancedFiltersPanelElement.classList.add('hidden');

            let label = null;
            let style = 'default';
            let id = null;

            this.presetFilterList.forEach(item => {
                if (typeof item !== 'string' && item.name === presetName) {
                    label = item.label || false;
                    style = item.style || 'default';
                    id = item.id;
                }
            });

            label = label || this.translate(this.presetName!, 'presetFilters', this.entityType);

            filterLabel = label;
            filterStyle = style;

            if (id) {
                this.$el.find('ul.dropdown-menu > li.divider.preset-control').removeClass('hidden');
                this.$el.find('ul.dropdown-menu > li.preset-control.remove-preset').removeClass('hidden');
            }
        } else {
            this.advancedFiltersPanelElement.classList.remove('hidden');

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

        this.currentFilterLabelList!.push(filterLabel);

        this.filtersButton.classList
            .remove(...[
                'text-primary',
                'text-danger',
                'text-success',
                'text-info',
                'text-warning',
            ]);


        if (filterStyle !== 'default') {
            this.filtersButton.classList.add(`text-${filterStyle}`);
        }

        presetName = presetName || '';

        this.$el
            .find('ul.filter-menu a.preset[data-name="'+presetName+'"]')
            .prepend('<span class="fas fa-check check-icon pull-right"></span>');
    }

    manageBoolFilters() {
        (this.boolFilterList ?? []).forEach((item) => {
            if (this.bool && this.bool[item]) {
                const label = this.translate(item, 'boolFilters', this.entityType);

                this.currentFilterLabelList!.push(label);
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

    private getFilterDataList() {
        const list = [];

        for (const field in this.advanced) {
            list.push({
                key: `filter-${field}`,
                name: field,
            });
        }

        return list;
    }

    private async updateCollection() {
        this.collection.abortLastFetch();
        this.collection.reset();
        this.collection.where = this.searchManager.getWhere();
        this.collection.offset = 0;

        Ui.notifyWait();

        await this.collection.fetch();

        Ui.notify(false);
    }

    private getPresetFilterList(): PresetFilter[] {
        const output: any = [];

        this.presetFilterList.forEach(item => {
            if (typeof item == 'string') {
                item = {name: item};
            }

            output.push(item);
        });

        return output;
    }

    private getPresetData() {
        let data = {};

        this.getPresetFilterList().forEach(item => {
            if (item.name === this.presetName) {
                data = Utils.clone(item.data || {});
            }
        });

        return data;
    }

    private getPrimaryFilterName() {
        let primaryFilterName = null;

        this.getPresetFilterList().forEach(item => {
            if (item.name !== this.presetName) {
                return;
            }

            if (!('data' in item)) {
                primaryFilterName = item.name;
            } else if (item.primary) {
                primaryFilterName = item.primary;
            }
        });

        return primaryFilterName;
    }

    private getPrimaryFilterStyle(): string | null {
        let style = null;

        this.getPresetFilterList().forEach(item => {
            if (item.name === this.primary) {
                style = item.style || 'default';
            }
        });

        return style;
    }

    private loadSearchData() {
        const searchData = this.searchManager.get();

        this.textFilter = searchData.textFilter ?? '';

        if ('presetName' in searchData) {
            this.presetName = searchData.presetName ?? null;
        }

        let primaryIsSet = false;

        if ('primary' in searchData) {
            this.primary = searchData.primary ?? null;

            if (!this.presetName) {
                this.presetName = this.primary;
            }

            primaryIsSet = true;
        }

        if (this.presetName) {
            this.advanced = _.extend(Utils.clone(this.getPresetData()), searchData.advanced);

            if (!primaryIsSet) {
                this.primary = this.getPrimaryFilterName();
            }
        } else {
            this.advanced = Utils.clone(searchData.advanced ?? {});
        }

        this.bool = searchData.bool ?? null;
    }


    private async createFilter(
        name: string,
        params: Record<string, any>,
        callback: CreateFilterCallback,
        noRender?: boolean,
    ) {

        params = params || {};

        let rendered = false;

        if (this.isRendered()) {
            rendered = true;

            this.advancedFiltersPanelElement.append(
                (() => {
                    const div = document.createElement('div');
                    div.dataset.name = name;
                    div.className = `filter filter-${name}`
                    return div;
                })()
            );
        }

        const view = new FilterView({
            name: name,
            model: this.model,
            params: params,
        });

        await this.assignView(`filter-${name}`, view, `.filter[data-name="${name}"]`);

        if (typeof callback === 'function') {
            view.once('after:render', () => callback(view));
        }

        if (rendered && !noRender) {
            view.render().then(() => {});
        }

        this.listenTo(view, 'change', () => {
            let toShowApply = this.isSearchedWithAdvancedFilter;

            if (!toShowApply) {
                const data = view.getFieldView()?.fetchSearch();

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
    }

    private fetch() {
        let value = this.textFilterInputElement?.value ?? '';
        value = value.trim();

        this.textFilter = value;

        this.bool = {};

        this.boolFilterList.forEach(name => {
            this.bool![name] = this.element
                .querySelector<HTMLInputElement>(`input[data-name="${name}"][data-role="boolFilterCheckbox"]`)
                ?.checked == true;
        });

        for (const field in this.advanced) {
            const view = this.getView(`filter-${field}`)
                ?.getView<BaseFieldView>('field');

            if (!view) {
                continue;
            }

            this.advanced[field] = view.fetchSearch();

            view.setSearchParams(Utils.clone(this.advanced[field] ?? {}));
        }
    }

    private updateSearch() {
        this.searchManager.set({
            textFilter: this.textFilter,
            advanced: this.advanced as Record<string, AdvancedFilter>,
            bool: this.bool ?? {},
            presetName: this.presetName ?? null,
            primary: this.primary,
        });
    }

    private getFilterFieldDataList() {
        const defs = [];

        for (const field of this.fieldFilterList!) {
            const o = {
                name: field,
                checked: (field in this.advanced),
                label: this.fieldFilterTranslations[field] || field,
            };

            defs.push(o);
        }

        return defs;
    }

    private showResetFiltersButton() {
        this.toShowApplyFiltersButton = true;
        this.toShowResetFiltersText = true;

        this.applyFiltersContainer.classList.remove('hidden');
        this.applyFiltersContainer.querySelector('.text-apply')?.classList.add('hidden');
        this.applyFiltersContainer.querySelector('.text-reset')?.classList.remove('hidden');
    }

    private showApplyFiltersButton() {
        this.toShowApplyFiltersButton = true;
        this.toShowResetFiltersText = false;

        this.applyFiltersContainer.classList.remove('hidden');

        this.applyFiltersContainer.querySelector('.text-reset')?.classList.add('hidden');
        this.applyFiltersContainer.querySelector('.text-apply')?.classList.remove('hidden');
    }

    private hideApplyFiltersButton() {
        this.toShowApplyFiltersButton = false;
        this.toShowResetFiltersText = false;

        this.applyFiltersContainer.classList.add('hidden');
    }

    selectPreviousPreset() {
        const list = Utils.clone(this.getPresetFilterList());

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
        const list = Utils.clone(this.getPresetFilterList());

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

    private processFieldFilterQuickSearch(text: string) {
        text = text.trim();
        text = text.toLowerCase();

        const lis = this.filterListElement!.querySelectorAll<HTMLLIElement>('li.filter-item');

        if (text === '') {
            lis.forEach(element => element.classList.remove('search-hidden'));

            return;
        }

        lis.forEach(element => element.classList.add('search-hidden'));

        this.fieldFilterList!.forEach(field => {
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
                Array.from(lis)
                    .filter(li => li.dataset.name === field)
                    .forEach(li => li.classList.remove('search-hidden'));
            }
        });
    }

    private resetFieldFilterQuickSearch() {
        if (!this.fieldQuickSearchInput) {
            return;
        }

        this.fieldQuickSearchInput.value = ''

        this.filterListElement?.querySelectorAll('li.filter-item')
            .forEach(li => li.classList.remove('search-hidden'));
    }

    private addFirstFieldFilter() {
        const first = this.filterListElement
            ?.querySelectorAll<HTMLLIElement>('li.filter-item:not(.hidden):not(.search-hidden)')[0] ?? null;

        if (!first) {
            return;
        }

        const name = first.dataset.name!;

        first.classList.add('hidden');

        this.closeAddFieldDropdown();
        this.addFilter(name);
        this.resetFieldFilterQuickSearch();
    }

    private closeAddFieldDropdown() {
        if (!this.addFilterButtonElement) {
            return;
        }

        const dropdown = this.addFilterButtonElement.parentElement?.querySelector('[data-toggle="dropdown"]');

        if (!dropdown) {
            return;
        }

        // @ts-ignore
        $(dropdown).dropdown('toggle');
    }

    private storeTextSearch() {
        if (!this.textFilter) {
            return;
        }

        if (this.textSearchStoringDisabled) {
            return;
        }

        this.storedTextSearchHelper.store(this.textFilter);
    }

    private async savePresetHandler() {
        const view = new SaveFiltersModalView();

        await this.assignView('savePreset', view);

        this.listenToOnce(view, 'save', (name: string) => {
            this.savePreset(name);

            view.close();
            this.removeFilters();
            this.createFilters(() => this.render());
        });

        await view.render();
    }
}

export default SearchView;
