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
			};
		},

		setup: function () {
			this.scope = this.collection.name;
			this.searchManager = this.options.searchManager;

			this.addReadyCondition(function () {
				return this.fields != null && this.moreFields != null;
			}.bind(this));
			
			this.boolFilters = this.getMetadata().get('entityDefs.' + this.scope + '.collection.boolFilters') || [];

			this._helper.layoutManager.get(this.scope, 'filtersAdvanced', function (list) {
				this.moreFields = list;
				this.tryReady();
			}.bind(this));

			this.getSearchData();

			this.model = new this.collection.model();
			this.model.clear();

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
				var name = $(e.currentTarget).data('name');
				this.advanced[name] = {};					
				$(e.currentTarget).closest('li').addClass('hide');					
				this.$el.find('.advanced-filters').append('<div class="filter filter-' + name + ' col-sm-4 col-md-3" />');
				
				this.createFilter(name, {}, function () {
					
				
					this.fetch();
					this.updateSearch();
				}.bind(this));
				this.updateAddFilterButton();
			},
			'click .advanced-filters a.remove-filter': function (e) { 
				var name = $(e.currentTarget).data('name');
				this.$el.find('ul.filter-list li[data-name="' + name + '"]').removeClass('hide');
				var container = this.getView('filter-' + name).$el.closest('div.filter');
				this.clearView('filter-' + name);
				container.remove();
				delete this.advanced[name];
				this.updateAddFilterButton();
			
				this.fetch();
				this.updateSearch();
			},
			'click button[data-action="reset"]': function (e) {
				for (var name in this.advanced) {
					this.clearView('filter-' + name);
				}

				this.searchManager.reset();
				this.getSearchData();

				this.render();
				this.updateCollection();
			},
			'click button[data-action="refresh"]': function (e) {
				this.notify('Loading...');
				this.listenToOnce(this.collection, 'sync', function () {
					this.notify(false);
				}.bind(this));
				this.collection.fetch();
			},
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
			this.$el.find('ul.basic-filter-menu').click(function (e) {
				e.stopPropagation();
			});
			this.updateAddFilterButton();
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
			this.notify('Searching...');
			this.listenTo(this.collection, 'sync', function () {
				this.notify(false);
			}.bind(this));
			this.collection.where = this.searchManager.getWhere();
			this.collection.fetch();
		},

		getSearchData: function () {
			var searchData = this.searchManager.get();
			this.filter = searchData.filter;
			this.basic = _.clone(searchData.basic);
			this.advanced = _.clone(searchData.advanced);
			this.bool = searchData.bool;
		},

		createFilter: function (name, params, callback) {
			params = params || {};

			var rendered = false;
			if (this.isRendered()) {
				rendered = true;
				Espo.Ui.notify('Loading...');
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
					Espo.Ui.notify(false);
				} else {

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

