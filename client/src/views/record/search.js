/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/record/search', 'view', function (Dep) {

    return Dep.extend({

        template: 'record/search',

        scope: null,

        searchManager: null,

        fieldList: ['name'],

        textFilter: '',

        primary: null,

        presetFilterList: null,

        advanced: null,

        bool: null,

        disableSavePreset: false,

        textFilterDisabled: false,

        toShowApplyFiltersButton: false,

        toShowResetFiltersText: false,

        isSearchedWithAdvancedFilter: false,

        viewModeIconClassMap: {
            list: 'fas fa-align-justify',
            kanban: 'fas fa-align-left fa-rotate-90',
        },

        data: function () {
             return {
                scope: this.scope,
                entityType: this.entityType,
                textFilter: this.textFilter,
                bool: this.bool || {},
                boolFilterList: this.boolFilterList,
                advancedFields: this.getAdvancedDefs(),
                filterDataList: this.getFilterDataList(),
                presetName: this.presetName,
                presetFilterList: this.getPresetFilterList(),
                leftDropdown: this.isLeftDropdown(),
                textFilterDisabled: this.textFilterDisabled,
                viewMode: this.viewMode,
                viewModeDataList: this.viewModeDataList || [],
                hasViewModeSwitcher: this.viewModeList && this.viewModeList.length > 1,
                isWide: this.options.isWide,
                toShowApplyFiltersButton: this.toShowApplyFiltersButton,
                toShowResetFiltersText: this.toShowResetFiltersText,
            };
        },

        setup: function () {
            this.entityType = this.collection.name;
            this.scope = this.options.scope || this.entityType;

            this.searchManager = this.options.searchManager;

            this.textFilterDisabled = this.options.textFilterDisabled || this.textFilterDisabled;

            if ('disableSavePreset' in this.options) {
                this.disableSavePreset = this.options.disableSavePreset;
            }

            this.viewMode = this.options.viewMode;
            this.viewModeList = this.options.viewModeList;

            this.addReadyCondition(() => {
                return this.fieldList != null && this.moreFieldList != null;
            });

            this.boolFilterList = Espo.Utils.clone(
                this.getMetadata().get('clientDefs.' + this.scope + '.boolFilterList') || []
            )
                .filter((item) => {
                    if (typeof item === 'string') {
                        return true;
                    }

                    item = item || {};

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
                .map((item) => {
                    if (typeof item === 'string') {
                        return item;
                    }

                    item = item || {};

                    return item.name;
                });

            var forbiddenFieldList = this.getAcl().getScopeForbiddenFieldList(this.entityType) || [];

            this._helper.layoutManager.get(this.entityType, 'filters', (list) => {
                this.moreFieldList = [];

                (list || []).forEach((field) => {
                    if (~forbiddenFieldList.indexOf(field)) {
                        return;
                    }

                    this.moreFieldList.push(field);
                });

                this.tryReady();
            });

            var filterList = this.options.filterList ||
                this.getMetadata().get(['clientDefs', this.scope, 'filterList']) || [];

            this.presetFilterList = Espo.Utils.clone(filterList).filter((item) => {
                if (typeof item === 'string') {
                    return true;
                }

                item = item || {};

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

            ((this.getPreferences().get('presetFilters') || {})[this.scope] || []).forEach((item) => {
                this.presetFilterList.push(item);
            });

            if (this.getMetadata().get('scopes.' + this.entityType + '.stream')) {
                this.boolFilterList.push('followed');
            }

            this.loadSearchData();

            if (this.hasAdvancedFilter()) {
                this.isSearchedWithAdvancedFilter = true;
            }

            if (this.presetName) {
                var hasPresetListed = false;

                for (var i in this.presetFilterList) {
                    var item = this.presetFilterList[i] || {};

                    var name = (typeof item === 'string') ? item : item.name;

                    if (name === this.presetName) {
                        hasPresetListed = true;

                        break;
                    }
                }

                if (!hasPresetListed) {
                    this.presetFilterList.push(this.presetName);
                }
            }

            this.model = new this.collection.model();

            this.model.clear();

            this.createFilters();

            this.setupViewModeDataList();

            this.listenTo(this.collection, 'order-changed', () => {
                this.controlResetButtonVisibility();
            });

            this.getHelper().processSetupHandlers(this, 'record/search');
        },

        setupViewModeDataList: function () {
            if (!this.viewModeList) {
                return [];
            }

            var list = [];

            this.viewModeList.forEach((item) => {
                var o = {
                    name: item,
                    title: this.translate(item, 'listViewModes'),
                    iconClass: this.viewModeIconClassMap[item]
                };

                list.push(o);
            });

            this.viewModeDataList = list;
        },

        setViewMode: function (mode, preventLoop, toTriggerEvent) {
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

            if (toTriggerEvent) {
                this.trigger('change-view-mode', mode);
            }
        },

        isLeftDropdown: function () {
            return this.presetFilterList.length ||
                this.boolFilterList.length ||
                Object.keys(this.advanced || {}).length;
        },

        handleLeftDropdownVisibility: function () {
            if (this.isLeftDropdown()) {
                this.$leftDropdown.removeClass('hidden');
            }
            else {
                this.$leftDropdown.addClass('hidden');
            }
        },

        createFilters: function (callback) {
            var i = 0;
            var count = Object.keys(this.advanced || {}).length;

            if (count === 0) {
                if (typeof callback === 'function') {
                    callback();
                }
            }

            for (var field in this.advanced) {
                this.createFilter(field, this.advanced[field], () =>{
                    i++;

                    if (i === count) {
                        if (typeof callback === 'function') {
                            callback();
                        }
                    }
                });
            }
        },

        events: {
            'keypress input[data-name="textFilter"]': function (e) {
                if (e.keyCode === 13) {
                    this.search();

                    this.hideApplyFiltersButton();
                }
            },

            'focus input[data-name="textFilter"]': function (e) {
                e.currentTarget.select();
            },

            'click .advanced-filters-apply-container a[data-action="applyFilters"]': function (e) {
                this.search();

                this.hideApplyFiltersButton();
            },

            'click button[data-action="search"]': function (e) {
                this.search();

                this.hideApplyFiltersButton();
            },

            'click a[data-action="addFilter"]': function (e) {
                var $target = $(e.currentTarget);
                var name = $target.data('name');

                $target.closest('li').addClass('hidden');

                this.addFilter(name);
            },

            'click .advanced-filters a.remove-filter': function (e) {
                var $target = $(e.currentTarget);

                var name = $target.data('name');

                this.removeFilter(name);
            },

            'click button[data-action="reset"]': function (e) {
                this.resetFilters();
            },

            'click button[data-action="refresh"]': function (e) {
                this.refresh();
            },

            'click a[data-action="selectPreset"]': function (e) {
                var presetName = $(e.currentTarget).data('name') || null;

                this.selectPreset(presetName);
            },

            'click .dropdown-menu a[data-action="savePreset"]': function (e) {
                this.createView('savePreset', 'views/modals/save-filters', {}, (view) => {
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

            'click .dropdown-menu a[data-action="removePreset"]': function (e) {
                var id = this.presetName;

                this.confirm(this.translate('confirmation', 'messages'), () => {
                    this.removePreset(id);
                });
            },

            'change .search-row ul.filter-menu input[data-role="boolFilterCheckbox"]': function (e) {
                e.stopPropagation();

                this.search();
                this.manageLabels();
            },

            'click [data-action="switchViewMode"]': function (e) {
                var mode = $(e.currentTarget).data('name');

                if (mode === this.viewMode) {
                    return;
                }

                this.setViewMode(mode, false, true);
            }
        },

        removeFilter: function (name) {
            this.$el.find('ul.filter-list li[data-name="' + name + '"]').removeClass('hidden');

            var container = this.getView('filter-' + name).$el.closest('div.filter');
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
                this.showResetFiltersButton();
            }
            else {
                if (!this.hasAdvancedFilter()) {
                    this.hideApplyFiltersButton();
                }
            }
        },

        addFilter: function (name) {
            this.advanced[name] = {};

            this.presetName = this.primary;

            this.createFilter(name, {}, (view) => {
                view.populateDefaults();

                this.fetch();
                this.updateSearch();

                if (view.getView('field').initialSearchIsNotIdle) {
                    this.showApplyFiltersButton();
                }
            });

            this.updateAddFilterButton();
            this.handleLeftDropdownVisibility();

            this.manageLabels();
            this.controlResetButtonVisibility();
        },

        refresh: function () {
            this.notify('Loading...');
            this.collection.abortLastFetch();
            this.collection.reset();

            this.collection.fetch().then(() => {
                Espo.Ui.notify(false);
            });
        },

        selectPreset: function (presetName, forceClearAdvancedFilters) {
            var wasPreset = !(this.primary == this.presetName);

            this.presetName = presetName;

            var advanced = this.getPresetData();

            this.primary = this.getPrimaryFilterName();

            var isPreset = !(this.primary === this.presetName);

            if (forceClearAdvancedFilters || wasPreset || isPreset || Object.keys(advanced).length) {
                this.removeFilters();
                this.advanced = advanced;
            }

            this.updateSearch();
            this.manageLabels();

            this.createFilters(function () {
                this.render();
            }.bind(this));

            this.updateCollection();
        },

        removeFilters: function () {
            this.$advancedFiltersPanel.empty();

            for (var name in this.advanced) {
                this.clearView('filter-' + name);
            }
        },

        resetFilters: function () {
            this.trigger('reset');

            this.collection.resetOrderToDefault();

            this.textFilter = '';

            this.selectPreset(this.presetName, true);

            this.hideApplyFiltersButton();
        },

        savePreset: function (name) {
            var id = 'f' + (Math.floor(Math.random() * 1000001)).toString();

            this.fetch();
            this.updateSearch();

            var presetFilters = this.getPreferences().get('presetFilters') || {};

            if (!(this.scope in presetFilters)) {
                presetFilters[this.scope] = [];
            }

            var data = {
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

            this.getPreferences().save({
                'presetFilters': presetFilters
            }, {patch: true});

            this.presetName = id;
        },

        removePreset: function (id) {
            var presetFilters = this.getPreferences().get('presetFilters') || {};

            if (!(this.scope in presetFilters)) {
                presetFilters[this.scope] = [];
            }

            var list;

            list = presetFilters[this.scope];

            list.forEach((item, i) => {
                if (item.id == id) {
                    list.splice(i, 1);
                }
            });

            list = this.presetFilterList;

            list.forEach((item, i) => {
                if (item.id == id) {
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
        },

        updateAddFilterButton: function () {
            var $ul = this.$el.find('ul.filter-list');

            if ($ul.children().not('.hidden').not('.dropdown-header').length == 0) {
                this.$el.find('button.add-filter-button').addClass('disabled');
            }
            else {
                this.$el.find('button.add-filter-button').removeClass('disabled');
            }
        },

        afterRender: function () {
            this.$filtersLabel = this.$el.find('.search-row span.filters-label');
            this.$filtersButton = this.$el.find('.search-row button.filters-button');
            this.$leftDropdown = this.$el.find('div.search-row div.left-dropdown');
            this.$resetButton = this.$el.find('[data-action="reset"]');
            this.$applyFiltersContainer = this.$el.find('.advanced-filters-apply-container');

            this.updateAddFilterButton();

            this.$advancedFiltersPanel = this.$el.find('.advanced-filters');

            this.manageLabels();

            this.controlResetButtonVisibility();
        },

        manageLabels: function () {
            this.$el.find('ul.dropdown-menu > li.preset-control').addClass('hidden');

            this.currentFilterLabelList = [];

            this.managePresetFilters();
            this.manageBoolFilters();

            this.$filtersLabel.html(this.currentFilterLabelList.join(', '));
        },

        controlResetButtonVisibility: function () {
            var presetName = this.presetName || null;
            var primary = this.primary;

            var $resetButton = this.$resetButton;

            var toShow = false;

            if (this.textFilter) {
                toShow = true;
            } else {
                if (presetName && presetName != primary) {

                } else {
                    if (Object.keys(this.advanced).length) {
                        toShow = true;
                    }
                }
            }

            if (!toShow) {
                if (
                    this.collection.orderBy !== this.collection.defaultOrderBy ||
                    this.collection.order !== this.collection.defaultOrder
                ) {
                    toShow = true;
                }
            }

            if (toShow) {
                $resetButton.css('visibility', 'visible');
            } else {
                $resetButton.css('visibility', 'hidden');
            }
        },

        managePresetFilters: function () {
            var presetName = this.presetName || null;
            var data = this.getPresetData();
            var primary = this.primary;

            this.$el.find('ul.filter-menu a.preset span').remove();

            var filterLabel = this.translate('all', 'presetFilters', this.entityType);
            var filterStyle = 'default';

            if (!presetName && primary) {
                presetName = primary;
            }

            if (presetName && presetName != primary) {
                this.$advancedFiltersPanel.addClass('hidden');

                var label = null;
                var style = 'default';
                var id = null;

                this.presetFilterList.forEach((item) => {
                    if (item.name == presetName) {
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

            } else {
                this.$advancedFiltersPanel.removeClass('hidden');

                if (Object.keys(this.advanced).length !== 0) {
                    if (!this.disableSavePreset) {
                        this.$el.find('ul.dropdown-menu > li.divider.preset-control').removeClass('hidden');
                        this.$el.find('ul.dropdown-menu > li.preset-control.save-preset').removeClass('hidden');
                        this.$el.find('ul.dropdown-menu > li.preset-control.remove-preset').addClass('hidden');
                    }
                }

                if (primary) {
                    var label = this.translate(primary, 'presetFilters', this.entityType);
                    var style = this.getPrimaryFilterStyle();

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
        },

        manageBoolFilters: function () {
            (this.boolFilterList || []).forEach((item) => {
                if (this.bool[item]) {
                    var label = this.translate(item, 'boolFilters', this.entityType);

                    this.currentFilterLabelList.push(label);
                }
            });
        },

        search: function () {
            this.fetch();
            this.updateSearch();
            this.updateCollection();
            this.controlResetButtonVisibility();

            this.isSearchedWithAdvancedFilter = this.hasAdvancedFilter();
        },

        hasAdvancedFilter: function () {
            return Object.keys(this.advanced).length > 0;
        },

        getFilterDataList: function () {
            var arr = [];

            for (var field in this.advanced) {
                arr.push({
                    key: 'filter-' + field,
                    name: field,
                });
            }

            return arr;
        },

        updateCollection: function () {
            this.collection.abortLastFetch();
            this.collection.reset();
            this.collection.where = this.searchManager.getWhere();
            this.collection.offset = 0;

            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

            this.collection.fetch().then(() => {
                Espo.Ui.notify(false);
            });
        },

        getPresetFilterList: function () {
            var arr = [];

            this.presetFilterList.forEach((item) => {
            	if (typeof item == 'string') {
            		item = {name: item};
            	}

            	arr.push(item);
            });

            return arr;
        },

        getPresetData: function () {
            var data = {};

            this.getPresetFilterList().forEach((item) => {
                if (item.name == this.presetName) {
                    data = Espo.Utils.clone(item.data || {});

                    return;
                }
            });

            return data;
        },

        getPrimaryFilterName: function () {
            var primaryFilterName = null;

            this.getPresetFilterList().forEach(item => {
                if (item.name == this.presetName) {
                    if (!('data' in item)) {
                        primaryFilterName = item.name;
                    }
                    else if (item.primary) {
                        primaryFilterName = item.primary;
                    }
                }
            });

            return primaryFilterName;
        },

        getPrimaryFilterStyle: function () {
            var style = null;

            this.getPresetFilterList().forEach(item => {
                if (item.name == this.primary) {
                    style = item.style || 'default';
                }
            });

            return style;
        },

        loadSearchData: function () {
            var searchData = this.searchManager.get();

            this.textFilter = searchData.textFilter;

            if ('presetName' in searchData) {
                this.presetName = searchData.presetName;
            }

            var primaryIsSet = false;

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
        },

        createFilter: function (name, params, callback, noRender) {
            params = params || {};

            var rendered = false;

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
                el: this.options.el + ' .filter[data-name="' + name + '"]',
            }, (view) => {
                if (typeof callback === 'function') {
                    view.once('after:render', () => {
                        callback(view);
                    });
                }

                if (rendered && !noRender) {
                    view.render();
                }

                this.listenTo(view, 'change', () => {
                    var toShowApply = this.isSearchedWithAdvancedFilter;

                    if (!toShowApply) {
                        var data = view.getView('field').fetchSearch();

                        if (data) {
                            toShowApply = true;
                        }
                    }

                    if (!toShowApply) {
                        return;
                    }

                    this.showApplyFiltersButton();
                });
            });
        },

        fetch: function () {
            this.textFilter = (this.$el.find('input[data-name="textFilter"]').val() || '').trim();

            this.bool = {};

            this.boolFilterList.forEach(name => {
                this.bool[name] = this.$el
                    .find('input[data-name="' + name + '"][data-role="boolFilterCheckbox"]')
                    .prop('checked');
            });

            for (var field in this.advanced) {
                var view = this.getView('filter-' + field).getView('field');

                this.advanced[field] = view.fetchSearch();

                view.searchParams = this.advanced[field];
            }
        },

        updateSearch: function () {
            this.searchManager.set({
                textFilter: this.textFilter,
                advanced: this.advanced,
                bool: this.bool,
                presetName: this.presetName,
                primary: this.primary,
            });
        },

        getAdvancedDefs: function () {
            var defs = [];

            for (var i in this.moreFieldList) {
                var field = this.moreFieldList[i];

                var o = {
                    name: field,
                    checked: (field in this.advanced),
                };

                defs.push(o);
            }

            return defs;
        },

        showResetFiltersButton: function () {
            this.toShowApplyFiltersButton = true;
            this.toShowResetFiltersText = true;

            this.$applyFiltersContainer.removeClass('hidden');

            this.$applyFiltersContainer.find('.text-apply').addClass('hidden');
            this.$applyFiltersContainer.find('.text-reset').removeClass('hidden');
        },

        showApplyFiltersButton: function () {
            this.toShowApplyFiltersButton = true;
            this.toShowResetFiltersText = false;

            this.$applyFiltersContainer.removeClass('hidden');

            this.$applyFiltersContainer.find('.text-reset').addClass('hidden');
            this.$applyFiltersContainer.find('.text-apply').removeClass('hidden');
        },

        hideApplyFiltersButton: function () {
            this.toShowApplyFiltersButton = false;
            this.toShowResetFiltersText = false;

            this.$applyFiltersContainer.addClass('hidden');
        },

    });
});
