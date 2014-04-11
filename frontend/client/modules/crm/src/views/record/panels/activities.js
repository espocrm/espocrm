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

Espo.define('Crm:Views.Record.Panels.Activities', 'Views.Record.Panels.Relationship', function (Dep) {

	return Dep.extend({

		name: 'activities',

		template: 'crm:record.panels.activities',

		scopeList: ['Meeting', 'Call'],

		sortBy: 'dateStart',

		asc: false,
		
		actions: [
			{
				action: 'createActivity',
				label: 'Schedule Meeting',
				data: {
					link: 'meetings',
					status: 'Planned',
				},
				acl: 'edit',
				aclScope: 'Meeting',
			},
			{
				action: 'createActivity',
				label: 'Schedule Call',
				data: {
					link: 'calls',
					status: 'Planned',
				},
				acl: 'edit',
				aclScope: 'Call',
			},
			{
				action: 'composeEmail',
				label: 'Compose Email',
				acl: 'edit',
				aclScope: 'Email',
			}
		],

		listLayout: {
			'Meeting': {
				rows: [
					[
						{name: 'ico', view: 'Crm:Fields.Ico'},
						{
							name: 'name',
							link: true,
						},
					],
					[
						{name: 'assignedUser'},
						{name: 'dateStart'},
					]
				]
			},
			'Call': {
				rows: [
					[
						{name: 'ico', view: 'Crm:Fields.Ico'},
						{
							name: 'name',
							link: true,
						},
					],
					[
						{name: 'assignedUser'},
						{name: 'dateStart'},
					]
				]
			}
		},

		where: {
			scope: false,
		},
		
		events: _.extend({
			'click button.scope-switcher': function (e) {
				var $target = $(e.currentTarget);
				this.$el.find('button.scope-switcher').removeClass('active');
				$target.addClass('active');
				this.where.scope = $target.data('scope') || false;
				
				this.listenToOnce(this.collection, 'sync', function () {
					this.notify(false);
				}.bind(this));
				this.notify('Loading...');
				this.collection.fetch();
				
				this.currentTab = this.where.scope || 'all';
				this.getStorage().set('state', this.getStorageKey(), this.currentTab);					
			}
		}, Dep.prototype.events),

		data: function () {
			return {
				currentTab: this.currentTab,
				scopeList: this.scopeList
			};
		},
		
		getStorageKey: function () {
			return 'activities-' + this.model.name + '-' + this.name;
		},

		setup: function () {
		
			this.currentTab = this.getStorage().get('state', this.getStorageKey()) || 'all';
			
			if (this.currentTab != 'all') {
				this.where = {scope: this.currentTab};
			}
			
			this.seeds = {};

			this.wait(true);
			var i = 0;
			this.scopeList.forEach(function (scope) {
				this.getModelFactory().getSeed(scope, function (seed) {
					this.seeds[scope] = seed;
					i++;
					if (i == this.scopeList.length) {
						this.wait(false);
					}
				}.bind(this));
			}.bind(this));
		},

		afterRender: function () {
			var url = 'Activities/' + this.model.name + '/' + this.model.id + '/' + this.name;						

			this.collection = new Espo.MultiCollection();
			this.collection.seeds = this.seeds;
			this.collection.url = url;
			this.collection.where = this.where;
			this.collection.sortBy = this.sortBy;
			this.collection.asc = this.asc;
			this.collection.maxSize = this.getSettings().get('recordsPerPageSmall') || 5;

			this.listenToOnce(this.collection, 'sync', function () {
				this.createView('list', 'Record.ListExpanded', {
					el: this.$el.selector + ' > .list-container',
					pagination: false,
					type: 'listRelationship',
					rowButtons: 'Record.ListButtons.RelationshipLess',
					checkboxes: false,
					collection: this.collection,
					listLayout: this.listLayout,
				}, function (view) {
					view.render();
				});
			}.bind(this));			
			this.collection.fetch();
		},

		actionCreateActivity: function (data) {
			var self = this;
			var link = data.link;
			var scope = this.model.defs['links'][link].entity;
			var foreignLink = this.model.defs['links'][link].foreign;

			this.notify('Loading...');
			this.getModelFactory().create(scope, function (model) {
				this.createView('quickCreate', 'EditModal', {
					scope: scope,
					relate: {
						model: this.model,
						link: foreignLink,
					},
					attributes: {
						status: data.status
					},
				}, function (view) {
					view.render();
					view.notify(false);
					view.once('after:save', function () {
						self.collection.fetch();
					});
				});
			}.bind(this));
		},
		
		actionComposeEmail: function () {
			var self = this;
			var link = 'emails';
			var scope = 'Email';
			
			var relate = null;				
			if ('emails' in this.model.defs['links']) {				
				relate = {
					model: this.model,
					link: this.model.defs['links']['emails'].foreign
				};
			}						

			this.notify('Loading...');
			this.getModelFactory().create(scope, function (model) {
				this.createView('quickCreate', 'ComposeEmail', {
					relate: relate,
					attributes: {
						status: 'Draft',
						to: this.model.get('emailAddress')
					},
				}, function (view) {
					view.render();
					view.notify(false);
					view.once('after:save', function () {
						self.collection.fetch();
					});
				});
			}.bind(this));
		},
	});
});

