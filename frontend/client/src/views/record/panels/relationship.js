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

Espo.define('Views.Record.Panels.Relationship', 'Views.Record.Panels.Bottom', function (Dep) {

	return Dep.extend({

		template: 'record.panels.relationship',
		
		rowButtonsView: 'Record.ListButtons.Relationship',

		setup: function () {
			this.link = this.panelName;				
			if (!(this.link in this.model.defs.links)) {
				throw new Error('Link \'' + this.link + '\' is not defined in model \'' + this.model.name + '\'');
			}
			this.scope = this.model.defs.links[this.link].entity;

			this.title = this.translate(this.link, 'links', this.model.name);
			var url = this.model.name + '/' + this.model.id + '/' + this.link;

			if (!this.defs.readOnly) {
				if (!('create' in this.defs)) {
					this.defs.create = true;
				}
				if (!('select' in this.defs)) {
					this.defs.select = true;
				}
			}

			this.buttons = {};
			if (this.getAcl().check(this.scope, 'edit')) {
				this.buttons.create = this.defs.create;
			}
			
			this.actions = _.clone(this.defs.actions || []);

			if (this.defs.select) {
				this.actions.unshift({
					label: 'Select',
					action: 'selectRelated',
					data: {
						link: this.link,
					}
				});
			}
			
		 	var type = 'listSmall';
		 	var listLayout = null;
			var layout = this.defs.layout || null;
		 	if (layout) {
			 	if (typeof layout == 'string') {
			 		type = layout;
			 	} else {
			 		type = 'listRelationship';
			 		listLayout = layout;
			 	}
		 	}
		 	var sortBy = this.defs.sortBy || null;
		 	var asc = this.defs.asc || null;

			this.wait(true);
			this.getCollectionFactory().create(this.scope, function (collection) {			
				collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 5;
			
				collection.url = collection.urlRoot = url;
				if (sortBy) {
					collection.sortBy = sortBy;
				}
				if (asc) {
					collection.asc = asc;
				}
				this.collection = collection;				
				
				this.once('after:render', function () {					
					collection.once('sync', function () {
						this.createView('list', 'Record.List', {
							collection: collection,
							type: type,
							listLayout: listLayout,
							checkboxes: false,
							rowButtons: this.defs.readOnly ? false : (this.defs.rowButtons || this.rowButtonsView),
							el: this.options.el + ' .list-container',
						}, function (view) {
							view.render();
						});
					}, this);
					collection.fetch();
				}, this);
				

				
				this.wait(false);
			}.bind(this));
		},
		
		getActions: function () {
			return this.actions || [];
		},
		
		getButtons: function () {
			if (this.buttons && this.buttons.create) {
				return [{
					title: 'Create',
					action: 'createRelated',
					link: this.link,
					acl: 'edit',
					aclScope: this.scope,
					html: '<span class="glyphicon glyphicon-plus"></span>',
				}];
			}
			return [];
		},

		actionEditRelated: function (id) {
			this.notify('Loading...');
			this.createView('quickEdit', 'Modals.Edit', {
				scope: this.collection.get(id).name,
				id: id
			}, function (view) {
				view.once('after:render', function () {
					Espo.Ui.notify(false);
				});
				view.render();
				view.once('after:save', function () {
					this.collection.fetch();
				}, this);
			}.bind(this));
		},

		actionUnlinkRelated: function (id) {
			var self = this;
			if (confirm(this.translate('Are you sure?'))) {
				var model = this.collection.get(id);
				self.notify('Unlinking...');
				$.ajax({
					url: self.collection.url,
					type: 'DELETE',
					data: JSON.stringify({
						id: id
					}),
					contentType: 'application/json',
					success: function () {
						self.notify('Unlinked', 'success');
						self.collection.fetch();
					},
					error: function () {
						self.notify('Error occurred', 'error');
					},
				});
			}
		},
		
		actionRemoveRelated: function (id) {
			var self = this;
			if (confirm(this.translate('Are you sure?'))) {
				var model = this.collection.get(id);
				self.notify('Removing...');
				model.destroy({
					success: function () {
						self.notify('Removed', 'success');
						self.collection.fetch();
					},
				});
			}
		},
	});
});

