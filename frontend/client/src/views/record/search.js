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

		textFilter: '',

		advanced: null,

		bool: null,
		
		disableSavePreset: false,

		data: function () {
			return {
				scope: this.scope,
				textFilter: this.textFilter,
				bool: this.bool || {},
				boolFilters: this.boolFilters,
				advancedFields: this.getAdvancedDefs(),
				filterList: this.getFilterList(),
				presetName: this.presetName,
				presetFilters: this.presetFilters,
				leftDropdown: this.presetFilters.length || this.boolFilters.length
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
			
			this.boolFilters = this.getMetadata().get('clientDefs.' + this.scope + '.boolFilters') || [];

			this._helper.layoutManager.get(this.scope, 'filters', function (list) {
				this.moreFields = list;
				this.tryReady();
			}.bind(this));
			
			this.presetFilters = _.clone(this.getMetadata().get('clientDefs.' + this.scope + '.presetFilters') || []);			
			((this.getPreferences().get('presetFilters') || {})[this.scope] || []).forEach(function (item) {
				this.presetFilters.push(item);
			}, this);

			this.loadSearchData();

			this.model = new this.collection.model();
			this.model.clear();			

			this.createFilters();
		},
		
		createFilters: function (callback) {			
			var i = 0;
			var count = Object.keys(this.advanced).length;
			
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
				
				this.presetName = null;
				
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
				
				this.presetName = null;
				
				this.updateAddFilterButton();				
			
				this.fetch();
				this.updateSearch();
				
				this.managePresetFilters();
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
				this.presetName = $(e.currentTarget).data('name') || null;
				
				this.removeFilters();
								
				this.advanced = this.getPresetData();	
				this.updateSearch();
				
				this.managePresetFilters();
				
				this.createFilters(function () {
					this.render();
				}.bind(this));
				this.updateCollection();
			},
			'click .advanced-filters-bar a[data-action="showFiltersPanel"]': function (e) {
				this.$advancedFiltersPanel.removeClass('hidden');
			},
			'click .advanced-filters-bar a[data-action="savePreset"]': function (e) {
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
			'click .advanced-filters-bar a[data-action="removePreset"]': function (e) {
				var id = $(e.currentTarget).data('id');
				if (confirm(this.translate('Are you sure?'))) {
					this.removePreset(id);
				}
			},
			'change .search-row ul.filter-menu input[data-role="boolFilterCheckbox"]': function (e) {
				e.stopPropagation();
				this.search();
			}
		},
		
		removeFilters: function () {
			this.$advancedFiltersPanel.empty();			
			for (var name in this.advanced) {				
				this.clearView('filter-' + name);
			}
		},
		
		resetFilters: function () {
			this.removeFilters();
				
			this.presetName = null;

			this.searchManager.reset();
			this.loadSearchData();

			this.render();
			this.updateCollection();
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
				data: this.advanced
			};
			
			presetFilters[this.scope].push(data);
			
			this.presetFilters.push(data);
			
			this.getPreferences().set('presetFilters', presetFilters);
			this.getPreferences().save({patch: true});
			this.getPreferences().trigger('update');
			
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
			
			list = this.presetFilters;
			list.forEach(function (item, i) {
				if (item.id == id) {
					list.splice(i, 1);
					return;				
				}
			}, this);		

			
			this.getPreferences().set('presetFilters', presetFilters);
			this.getPreferences().save({patch: true});
			this.getPreferences().trigger('update');			
			
			this.presetName = null;
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
			this.updateAddFilterButton();
			
			this.$advancedFiltersBar = this.$el.find('.advanced-filters-bar');
			this.$advancedFiltersPanel = this.$el.find('.advanced-filters');
			
			this.managePresetFilters();
		},
		
		managePresetFilters: function () {			
			var name = this.presetName || null;
			var data = this.getPresetData();
			
			this.$el.find('ul.filter-menu a.preset span').remove();
			
			if (name) {
				this.$advancedFiltersPanel.addClass('hidden');
								
				var label = null;
				var style = null;
				var id = null;
				this.presetFilters.forEach(function (item) {
					if (item.name == this.presetName) {
						label = item.label || false;
						style = item.style || 'default';
						id = item.id;
						return;
					}
				}, this);			
				label = label || this.translate(this.presetName, 'presetFilters', this.scope);				
				
				var barContentHtml = '<a href="javascript:" class="label label-'+style+'" data-action="showFiltersPanel">' + label + '</a>';
				if (id) {
					barContentHtml += ' <a href="javascript:" title="'+this.translate('Remove')+'" class="small" data-action="removePreset" data-id="'+id+'"><span class="glyphicon glyphicon-remove"></span></a>';
				}
				
				this.$advancedFiltersBar.removeClass('hidden').html(barContentHtml);		
			} else {
				this.$advancedFiltersPanel.removeClass('hidden');				
				
				if (Object.keys(this.advanced).length !== 0) {					
					if (!this.disableSavePreset) {					
						var barHtml = '<a href="javascript:" class="small" data-action="savePreset">' + this.translate('Save Filters') + '</a>';
						this.$advancedFiltersBar.removeClass('hidden').html(barHtml);	
					} else {
						this.$advancedFiltersBar.addClass('hidden').empty();
					}					
					return;
				} else {
					this.$advancedFiltersBar.addClass('hidden').empty();	
				}				
			}

			name = name || '';
			
			this.$el.find('ul.filter-menu a.preset[data-name="'+name+'"]').prepend('<span class="glyphicon glyphicon-ok pull-right"></span>');
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

		
		getPresetData: function () {
			var data = {};
			this.presetFilters.forEach(function (item) {
				if (item.name == this.presetName) {
					data = _.clone(item.data);
					return;
				}
			}, this);
			return data;
		},

		loadSearchData: function () {
			var searchData = this.searchManager.get();
			this.textFilter = searchData.textFilter;
			
			if ('presetName' in searchData) {
				this.presetName = searchData.presetName;	
			}		
			
			if (this.presetName) {
				this.advanced = this.getPresetData();
			} else {
				this.advanced = _.clone(searchData.advanced);
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
				textFilter: this.textFilter,
				advanced: this.advanced,
				bool: this.bool,
				presetName: this.presetName
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

