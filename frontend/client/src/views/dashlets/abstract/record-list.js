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

Espo.define('Views.Dashlets.Abstract.RecordList', 'Views.Dashlets.Abstract.Base', function (Dep) {

	return Dep.extend({

		name: 'Leads',

		scope: null,

		listViewColumn: 'Record.List',

		listViewExpanded: 'Record.ListExpanded',

		_template: '<div class="list-container">{{{list}}}</div>',

		layoutType: 'expanded',
		
		optionsFields: _.extend(_.clone(Dep.prototype.optionsFields), {
			'displayRecords': {
				type: 'enumInt',
				options: [3,4,5,10,15],							
			},
			'isDobleHeight': {
				type: 'bool',							
			}
		}),			

		afterRender: function () {
			this.getCollectionFactory().create(this.scope, function (collection) {

				var searchManager = new Espo.SearchManager(collection, 'list', null, this.getDateTime(), this.getOption('searchData'));

				this.collection = collection;
				collection.sortBy = this.getOption('sortBy') || this.collection.sortBy;
				collection.asc = this.getOption('asc') || this.collection.asc;
				collection.maxSize = this.getOption('displayRecords');
				collection.where = searchManager.getWhere();
				
				
				var viewName = (this.layoutType == 'expanded') ? this.listViewExpanded : this.listViewColumn;

				this.listenToOnce(collection, 'sync', function () {
					this.createView('list', viewName, {
						collection: collection,
						el: this.$el.selector + ' .list-container',
						pagination: this.getOption('pagination') ? 'bottom' : false,
						type: 'listDashlet',
						rowActionsView: false,
						checkboxes: false,
						showMore: true,
						listLayout: this.getOption(this.layoutType + 'Layout')
					}, function (view) {
						view.render();
					});
				}.bind(this));
				
				collection.fetch();

			}.bind(this));
		},
		
		actionRefresh: function () {			
			this.collection.fetch();
		},
	});
});

