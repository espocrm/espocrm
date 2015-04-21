/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/

Espo.define('Views.Record.Search', 'View', function (Dep) {

    return Dep.extend({

        template: 'record.search',

        scope: null,

        searchManager: null,

        fields: ['name'],

        textFilter: '',

        primary: null,

        presetFilterList: null,

        advanced: null,

        bool: null,

        disableSavePreset: false,

        data: function () {
            return {
                scope: this.scope,
                textFilter: this.textFilter,
                bool: this.bool || {},
                boolFilterList: this.boolFilterList,
                advancedFields: this.getAdvancedDefs(),
                filterList: this.getFilterList(),
                presetName: this.presetName,
                presetFilterList: this.getPresetFilterList(),
                leftDropdown: this.presetFilterList.length || this.boolFilterList.length
            };
        },

        setup: function () {
            this.scope = this.collection.name;
            this.searchManager = this.options.searchManager;

            if ('disableSavePreset' in this.options) {
                this.disableSavePreset = this.options.disableSavePreset;
            }

            this.addReadyCondition(function () {
                return this.fields != null && this.moreFields != null;
            }.bind(this));

            this.boolFilterList = Espo.Utils.clone(this.getMetadata().get('clientDefs.' + this.scope + '.boolFilterList') || []);

            this._helper.layoutManager.get(this.scope, 'filters', function (list) {
                this.moreFields = list;
                this.tryReady();
            }.bind(this));

            this.presetFilterList = Espo.Utils.clone(this.getMetadata().get('clientDefs.' + this.scope + '.filterList') || []);
            ((this.getPreferences().get('presetFilters') || {})[this.scope] || []).forEach(function (item) {
                this.presetFilterList.push(item);
            }, this);

            this.loadSearchData();

            this.model = new this.collection.model();
            this.model.clear();

            this.createFilters();
        },

        createFilters: function (callback) {
            var i = 0;
            var count = Object.keys(this.advanced || {}).length;

            if (count == 0) {
                if (typeof callback === 'function') {
                    callback();
                }
            }

            for (var field in this.advanced) {
                this.createFilter(field, this.advanced[field], function () {
                    i++;
                    if (i == count) {
                        if (typeof callback === 'function') {
                            callback();
                        }
                    }
                });
            }
        },

        events: {
            'keypress input[name="textFilter"]': function (e) {
                if (e.keyCode == 13) {
                    this.search();
                }
            },
            'click button[data-action="search"]': function (e) {
                this.search();
            },
            'click a[data-action="addFilter"]': function (e) {
                var $target = $(e.currentTarget);
                var name = $target.data('name');
                this.advanced[name] = {};

                $target.closest('li').addClass('hide');

                this.presetName = this.primary;

                this.createFilter(name, {}, function () {
                    this.fetch();
                    this.updateSearch();
                }.bind(this));
                this.updateAddFilterButton();

                this.manageLabels();
            },
            'click .advanced-filters a.remove-filter': function (e) {
                var $target = $(e.currentTarget);
                var name = $target.data('name');

                this.$el.find('ul.filter-list li[data-name="' + name + '"]').removeClass('hide');
                var container = this.getView('filter-' + name).$el.closest('div.filter');
                this.clearView('filter-' + name);
                container.remove();
                delete this.advanced[name];


                this.presetName = this.primary;

                this.updateAddFilterButton();

                this.fetch();
                this.updateSearch();

                this.manageLabels();
            },
            'click button[data-action="reset"]': function (e) {
                this.resetFilters();
            },
            'click button[data-action="refresh"]': function (e) {
                this.notify('Loading...');
                this.listenToOnce(this.collection, 'sync', function () {
                    this.notify(false);
                }.bind(this));

                this.collection.reset();
                this.collection.fetch();

            },
            'click a[data-action="selectPreset"]': function (e) {
                var presetName = $(e.currentTarget).data('name') || null;
                this.selectPreset(presetName);
            },
            'click .advanced-filters-bar a[data-action="showFiltersPanel"]': function (e) {
                this.$advancedFiltersPanel.removeClass('hidden');
            },
            'click .dropdown-menu a[data-action="savePreset"]': function (e) {
                this.createView('savePreset', 'Modals.SaveFilters', {}, function (view) {
                    view.render();
                    this.listenToOnce(view, 'save', function (name) {
                        this.savePreset(name);
                        view.close();

                        this.removeFilters();
                        this.createFilters(function () {
                            this.render();
                        }.bind(this));

                    }, this);
                }.bind(this));
            },
            'click .dropdown-menu a[data-action="removePreset"]': function (e) {
                var id = this.presetName;
                if (confirm(this.translate('confirmation', 'messages'))) {
                    this.removePreset(id);
                }
            },
            'change .search-row ul.filter-menu input[data-role="boolFilterCheckbox"]': function (e) {
                e.stopPropagation();
                this.search();
                this.manageLabels();
            }
        },

        selectPreset: function (presetName) {
            this.presetName = presetName;

            this.removeFilters();

            this.advanced = this.getPresetData();
            this.primary = this.getPrimaryFilterName();

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

            this.textFilter = '';
            this.selectPreset(this.presetName);

            /*if (!this.searchManager.emptyOnReset) {
	            this.textFilter = '';
	            this.selectPreset(this.presetName);
        	} else {

	            this.removeFilters();

	            this.presetName = null;

	            this.searchManager.reset();
	            this.loadSearchData();

	            this.createFilters(function () {
	                this.render();
	            }.bind(this));

	            this.updateCollection();
        	}*/
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
                primary: this.primary
            };

            presetFilters[this.scope].push(data);

            this.presetFilterList.push(data);

            this.getPreferences().once('sync', function () {
                this.getPreferences().trigger('update');
                this.updateSearch()
            }, this);

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
            list.forEach(function (item, i) {
                if (item.id == id) {
                    list.splice(i, 1);
                    return;
                }
            }, this);

            list = this.presetFilterList;
            list.forEach(function (item, i) {
                if (item.id == id) {
                    list.splice(i, 1);
                    return;
                }
            }, this);


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
            if ($ul.children().not('.hide').size() == 0) {
                this.$el.find('button.add-filter-button').addClass('disabled');
            } else {
                this.$el.find('button.add-filter-button').removeClass('disabled');
            }
        },

        afterRender: function () {
        	this.$filtersLabel = this.$el.find('.search-row span.filters-label');
        	this.$filtersButton = this.$el.find('.search-row button.filters-button');

            this.updateAddFilterButton();

            this.$advancedFiltersBar = this.$el.find('.advanced-filters-bar');
            this.$advancedFiltersPanel = this.$el.find('.advanced-filters');

            this.manageLabels();
        },

        manageLabels: function () {
            this.$advancedFiltersBar.empty().addClass('hidden');

            this.$el.find('ul.dropdown-menu > li.preset-control').addClass('hidden');

            this.currentFilterLabelList = [];

            this.managePresetFilters();
            this.manageBoolFilters();

            this.$filtersLabel.html(this.currentFilterLabelList.join(', '));
        },

        addLabelHtml: function (label, style, id, noAction) {
            var action = "showFiltersPanel";
            var cursor = 'pointer';
            var tag = 'a';
            if (noAction) {
                action = "NO_ACTION";
                tag = 'span';
                cursor = 'default';
            }

            var barContentHtml = '<'+tag+' href="javascript:" style="cursor: '+cursor+';" class="label label-'+style+'" data-action="'+action+'">' + label + '</'+tag+'>';
            if (id) {
                barContentHtml += ' <a href="javascript:" title="'+this.translate('Remove')+'" class="small" data-action="removePreset" data-id="'+id+'"><span class="glyphicon glyphicon-remove"></span></a>';
            }
            barContentHtml = '<span style="margin-right: 10px;">' + barContentHtml + '</span>'

            this.$advancedFiltersBar.append($(barContentHtml));
            this.$advancedFiltersBar.removeClass('hidden');

        },

        managePresetFilters: function () {
            var presetName = this.presetName || null;
            var data = this.getPresetData();
            var primary = this.primary;

            this.$el.find('ul.filter-menu a.preset span').remove();

            var filterLabel = this.translate('All');
            var filterStyle = 'default';

            if (presetName && presetName != primary) {
                this.$advancedFiltersPanel.addClass('hidden');

                var label = null;
                var style = 'default';
                var id = null;

                this.presetFilterList.forEach(function (item) {
                    if (item.name == presetName) {
                        label = item.label || false;
                        style = item.style || 'default';
                        id = item.id;
                        return;
                    }
                }, this);
                label = label || this.translate(this.presetName, 'presetFilters', this.scope);

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
                	var label = this.translate(primary, 'presetFilters', this.scope);
                	var style = this.getPrimaryFilterStyle();
                	filterLabel = label;
                	filterStyle = style;
                }
            }

            this.currentFilterLabelList.push(filterLabel);

            this.$filtersButton.removeClass('btn-default')
                               .removeClass('btn-primary')
                               .removeClass('btn-danger')
                               .removeClass('btn-success')
                               .removeClass('btn-info');
            this.$filtersButton.addClass('btn-' + style);

            presetName = presetName || '';

            this.$el.find('ul.filter-menu a.preset[data-name="'+presetName+'"]').prepend('<span class="glyphicon glyphicon-ok pull-right"></span>');
        },

        manageBoolFilters: function () {
            (this.boolFilterList || []).forEach(function (item) {
                if (this.bool[item]) {
                	var label = this.translate(item, 'boolFilters', this.scope);
                	this.currentFilterLabelList.push(label);
                }
            }, this);
        },

        search: function () {
            this.fetch();
            this.updateSearch();
            this.updateCollection();
        },

        getFilterList: function () {
            var arr = [];
            for (var field in this.advanced) {
                arr.push('filter-' + field);
            }
            return arr;
        },

        updateCollection: function () {
            this.collection.reset();
            this.notify('Please wait...');
            this.listenTo(this.collection, 'sync', function () {
                this.notify(false);
            }.bind(this));
            this.collection.where = this.searchManager.getWhere();
            this.collection.fetch();
        },

		getPresetFilterList: function () {
			var arr = [];
            this.presetFilterList.forEach(function (item) {
            	if (typeof item == 'string') {
            		item = {name: item};
            	}
            	arr.push(item);
            }, this);
            return arr;
		},

        getPresetData: function () {
            var data = {};
            this.getPresetFilterList().forEach(function (item) {
                if (item.name == this.presetName) {
                    data = Espo.Utils.clone(item.data || {});
                    return;
                }
            }, this);
            return data;
        },

        getPrimaryFilterName: function () {
            var primaryFilterName = null;
            this.getPresetFilterList().forEach(function (item) {
                if (item.name == this.presetName) {
                    if (!('data' in item)) {
                        primaryFilterName = item.name;
                    } else if (item.primary) {
                        primaryFilterName = item.primary;
                    }
                    return;
                }
            }, this);
            return primaryFilterName;
        },

        getPrimaryFilterStyle: function () {
            var style = null;
            this.getPresetFilterList().forEach(function (item) {
                if (item.name == this.primary) {
                    style = item.style || 'default';
                    return;
                }
            }, this);
            return style;
        },

        loadSearchData: function () {
            var searchData = this.searchManager.get();
            this.textFilter = searchData.textFilter;

            if ('presetName' in searchData) {
                this.presetName = searchData.presetName;
            }

            if ('primary' in searchData) {
                this.primary = searchData.primary;
            }

            if (this.presetName) {
                this.advanced = _.extend(Espo.Utils.clone(this.getPresetData()), searchData.advanced);

                this.primary = this.getPrimaryFilterName();
            } else {
                this.advanced = Espo.Utils.clone(searchData.advanced);
            }
            this.bool = searchData.bool;
        },

        createFilter: function (name, params, callback, noRender) {
            params = params || {};

            var rendered = false;
            if (this.isRendered()) {
                rendered = true;
                this.$advancedFiltersPanel.append('<div class="filter filter-' + name + ' col-sm-4 col-md-3" />');
            }

            this.createView('filter-' + name, 'Search.Filter', {
                name: name,
                model: this.model,
                params: params,
                el: this.options.el + ' .filter-' + name
            }, function (view) {
                if (typeof callback === 'function') {
                    view.once('after:render', function () {
                        callback();
                    });
                }
                if (rendered && !noRender) {
                    view.render();
                }
            }.bind(this));
        },

        fetch: function () {
            this.textFilter = this.$el.find('input[name="textFilter"]').val();

            this.bool = {};

            this.boolFilterList.forEach(function (name) {
                this.bool[name] = this.$el.find('input[name="' + name + '"]').prop('checked');
            }, this);

            for (var field in this.advanced) {
                var data = {};
                var method = 'fetch';
                var view = this.getView('filter-' + field).getView('field');
                this.advanced[field] = view.fetchSearch();
            }
        },

        updateSearch: function () {
            this.searchManager.set({
                textFilter: this.textFilter,
                advanced: this.advanced,
                bool: this.bool,
                presetName: this.presetName,
                primary: this.primary
            });
        },

        getAdvancedDefs: function () {
            var defs = [];
            for (var i in this.moreFields) {
                var field = this.moreFields[i];
                var o = {
                    name: field,
                    checked: (field in this.advanced),
                };
                defs.push(o);
            }
            return defs;
        },
    });
});

