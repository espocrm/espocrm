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

Espo.define('Views.List', 'Views.Main', function (Dep) {

	return Dep.extend({

		template: 'list',

		el: '#main',

		scope: null,

		name: 'List',

		views: {
			header: {
				selector: '> .page-header',
				view: 'Header'
			}
		},

		setup: function () {
			this.createView('search', 'Record.Search', {
				collection: this.collection,
				el: '#main > .search-container',
				searchManager: this.options.searchManager,
			});		

			this.menu.buttons.unshift({
				link: '#' + this.scope + '/create',
				label: 'Create ' +  this.scope,
				style: 'primary',
				acl: 'edit'
			});			
		},

		afterRender: function () {
			this.notify('Loading...');	
			
			var listViewName = this.getMetadata().get('clientDefs/' + this.name + '/recordViews/list') || 'Record.List';
			
			this.listenToOnce(this.collection, 'sync', function () {				
				this.createView('list', listViewName, {
					collection: this.collection,
					el: this.options.el + ' > .list-container',
				}, function (view) {
					view.render();
					view.notify(false);
					
					this.listenTo(this.getView('list'), 'sort', function (o) {
						// TODO store
					}.bind(this));
					
				}.bind(this));							
			}.bind(this));			
			this.collection.fetch();	
		},

		getHeader: function () {
			return this.getLanguage().translate(this.collection.name, 'scopeNamesPlural');
		},

		updatePageTitle: function () {
			this.setPageTitle(this.getLanguage().translate(this.collection.name, 'scopeNamesPlural'));
		},
	});
});

