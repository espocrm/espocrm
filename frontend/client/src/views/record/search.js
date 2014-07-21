/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

		filter: '',

		basic: null,

		advanced: null,

		bool: null,

		filterViews: null,

		data: function () {
			return {
				scope: this.scope,
				filter: this.filter,
				bool: this.bool || {},
				boolFilters: this.boolFilters,
				advancedFields: this.getAdvancedDefs(),
				filterList: this.getFilterList(),
				presetFilterName: this.presetFilterName,
				presetFilters: this.presetFilters,
				leftDropdown: this.presetFilters.length || this.boolFilters.length
			};
		},

		setup: function () {
			this.scope = this.collection.name;
			this.searchManager = this.options.searchManager;

			this.addReadyCondition(function () {
				return this.fields != null && this.moreFields != null;
			}.bind(this));
			
			this.boolFilters = this.getMetadata().get('entityDefs.' + this.scope + '.collection.boolFilters') || [];

			this._helper.layoutManager.get(this.scope, 'filters', function (list) {
				this.moreFields = list;
				this.tryReady();
			}.bind(this));
			
			this.presetFilters = this.getMetadata().get('clientDefs.' + this.scope + '.presetFilters') || [];

			this.loadSearchData();

			this.model = new this.collection.model();
			this.model.clear();			

			this.createFilters();
		},
		
		createFilters: function () {
			this.filterViews = [];
			for (var field in this.advanced) {
				this.createFilter(field, this.advanced[field]);
			}
		},

		events: {
			'keypress input[name="filter"]': function (e) {
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
				this.$advancedFiltersPanel.append('<div class="filter filter-' + name + ' col-sm-4 col-md-3" />');
				
				this.presetFilterName = null;
				
				this.createFilter(name, {}, function () {				
					this.fetch();
					this.updateSearch();
				}.bind(this));
				this.updateAddFilterButton();
				
				this.managePresetFilters();
			},
			'click .advanced-filters a.remove-filter': function (e) {			
				var $target = $(e.currentTarget);
				var name = $target.data('name');
				
				this.$el.find('ul.filter-list li[data-name="' + name + '"]').removeClass('hide');
				var container = this.getView('filter-' + name).$el.closest('div.filter');
				this.clearView('filter-' + name);				
				container.remove();
				delete this.advanced[name];
				
				this.presetFilterName = null;
				
				this.updateAddFilterButton();				
			
				this.fetch();
				this.updateSearch();
				
				this.managePresetFilters();
			},
			'click button[data-action="reset"]': function (e) {
				for (var name in this.advanced) {
					this.clearView('filter-' + name);
				}
				
				this.presetFilterName = null;

				this.searchManager.reset();
				this.loadSearchData();

				this.render();
				this.updateCollection();
			},
			'click button[data-action="refresh"]': function (e) {
				this.notify('Loading...');				
				this.listenToOnce(this.collection, 'sync', function () {
					this.notify(false);
				}.bind(this));
				
				this.collection.reset();
				this.collection.fetch();
				
			},
			'click a[data-action="selectPresetFilter"]': function (e) {
				this.presetFilterName = $(e.currentTarget).data('name') || null;
				
				for (var name in this.advanced) {
					this.clearView('filter-' + name);
				}				
				this.advanced = this.getPresetFilterData();								
				this.createFilters();				
				this.updateSearch();				
				this.render();
				this.updateCollection();
			},
			'click .advanced-filters-bar a[data-action="showFiltersPanel"]': function (e) {
				this.$advancedFiltersPanel.removeClass('hidden');
			}
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
			this.$el.find('ul.basic-filter-menu li.checkbox').click(function (e) {
				e.stopPropagation();
			});
			this.updateAddFilterButton();
			
			this.$advancedFiltersBar = this.$el.find('.advanced-filters-bar');
			this.$advancedFiltersPanel = this.$el.find('.advanced-filters');
			
			this.managePresetFilters();
		},
		
		managePresetFilters: function () {			
			var name = this.presetFilterName || null;
			this.$el.find('ul.basic-filter-menu a.preset span').remove();
			
			if (name) {
				this.$advancedFiltersPanel.addClass('hidden');
				
				var badgeHtml = '<a class="label label-default" data-action="showFiltersPanel">'+this.translate(name, 'presetFilters', this.scope)+'</a>';
				
				this.$advancedFiltersBar.html(badgeHtml);		
			} else {
				this.$advancedFiltersPanel.removeClass('hidden');				
				
				if (Object.keys(this.advanced).length !== 0) {
					var btnHtml = '<a href="javascript:" class="small" data-action="saveFilters">' + this.translate('Save Filters') + '</a>';
					this.$advancedFiltersBar.html(btnHtml);	
					return;
				} else {
					this.$advancedFiltersBar.empty();	
				}		
				
				
			}

			name = name || '';
			
			this.$el.find('ul.basic-filter-menu a.preset[data-name="'+name+'"]').append('<span class="glyphicon glyphicon-ok pull-right"></span>');
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
		
		getPresetFilterData: function () {
			var data = {};
			this.presetFilters.forEach(function (item) {
				if (item.name == this.presetFilterName) {
					data = _.clone(item.data);
					return;
				}
			}, this);
			return data;
		},

		loadSearchData: function () {
			var searchData = this.searchManager.get();
			this.filter = searchData.filter;
			this.basic = _.clone(searchData.basic);
			
			if ('presetFilterName' in searchData) {
				this.presetFilterName = searchData.presetFilterName;	
			}		
			
			if (this.presetFilterName) {
				this.advanced = this.getPresetFilterData();
			} else {
				this.advanced = _.clone(searchData.advanced);
			}
			this.bool = searchData.bool;
		},

		createFilter: function (name, params, callback) {
			params = params || {};

			var rendered = false;
			if (this.isRendered()) {
				rendered = true;
			}

			this.createView('filter-' + name, 'Search.Filter', {
				name: name,
				model: this.model,
				params: params,
				el: this.options.el + ' .filter-' + name
			}, function (view) {
				if (typeof callback != 'undefined') {
					view.once('after:render', function () {
						callback();
					});
				}
				if (rendered) {
					view.render();
				}
			}.bind(this));
		},

		fetch: function () {
			this.filter = this.$el.find('input[name="filter"]').val();

			this.basic = {
				name: true
			};

			this.bool = {};
			
			this.boolFilters.forEach(function (name) {
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
				filter: this.filter,
				basic: this.basic,
				advanced: this.advanced,
				bool: this.bool,
				presetFilterName: this.presetFilterName
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

