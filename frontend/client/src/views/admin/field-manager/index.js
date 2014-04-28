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

Espo.define('Views.Admin.FieldManager.Index', 'View', function (Dep) {

	return Dep.extend({

		template: 'admin.field-manager.index',

		scopeList: null,

		scope: null,

		type: null,

		data: function () {
			return {
				scopeList: this.scopeList,
				scope: this.scope,
			};
		},

		events: {
			'click #scopes-menu a.scope-link': function (e) {
				var scope = $(e.currentTarget).data('scope');
				this.openScope(scope);
			},
			
			'click #fields-content a.field-link': function (e) {
				var scope = $(e.currentTarget).data('scope');
				var field = $(e.currentTarget).data('field');
				this.openField(scope, field);
			},
			
			'click a[data-action="addField"]': function (e) {
				var scope = $(e.currentTarget).data('scope');
				var type = $(e.currentTarget).data('type');								
				this.createField(scope, type);
			},
		},

		setup: function () {
			this.scopeList = [];				
			var scopesAll = Object.keys(this.getMetadata().get('scopes'));;
			scopesAll.forEach(function (scope) {
				if (this.getMetadata().get('scopes.' + scope + '.entity')) {
					if (this.getMetadata().get('scopes.' + scope + '.customizable')) {
						this.scopeList.push(scope);
					}					
				}
			}.bind(this));

			this.scope = this.options.scope || null;
			this.field = this.options.field || null;		
			
			this.on('after:render', function () {				
				this.renderFieldsHeader();				
				if (!this.scope) {
					this.renderDefaultPage();
				} else {
					if (!this.field) {
						this.openScope(this.scope);
					} else {
						this.openField(this.scope, this.field);
					}
				}				
			});			
		},

		openScope: function (scope) {
			this.scope = scope;
			this.field = null;
			
			this.getRouter().navigate('#Admin/fieldManager/scope=' + scope, {trigger: false});
			
			this.notify('Loading...');			
			this.createView('content', 'Admin.FieldManager.List', {
				el: '#fields-content',
				scope: scope,
			}, function (view) {
				this.renderFieldsHeader();
				view.render();
				this.notify(false);				
				$(window).scrollTop(0);
			}.bind(this));
		},
		
		openField: function (scope, field) {
			this.scope = scope;
			this.field = field
			
			this.getRouter().navigate('#Admin/fieldManager/scope=' + scope + '&field=' + field, {trigger: false});
			
			this.notify('Loading...');			
			this.createView('content', 'Admin.FieldManager.Edit', {
				el: '#fields-content',
				scope: scope,
				field: field,
			}, function (view) {
				this.renderFieldsHeader();
				view.render();
				this.notify(false);
				$(window).scrollTop(0);
			}.bind(this));
		},
		
		createField: function (scope, type) {
			this.scope = scope;
			this.type = type;
			
			this.getRouter().navigate('#Admin/fieldManager/scope=' + scope + '&type=' + type + '&create=true', {trigger: false});
			
			this.notify('Loading...');			
			this.createView('content', 'Admin.FieldManager.Edit', {
				el: '#fields-content',
				scope: scope,
				type: type,
			}, function (view) {
				this.renderFieldsHeader();
				view.render();
				this.notify(false);
				$(window).scrollTop(0);
			}.bind(this));
		},

		renderDefaultPage: function () {
			$('#fields-header').html('').hide();
			$('#fields-content').html(this.translate('selectEntityType', 'messages', 'Admin'));
		},

		renderFieldsHeader: function () {
			if (!this.scope) {
				$('#fields-header').html('');
				return;
			}
			
			if (this.field) {
				$('#fields-header').show().html('<a href="#Admin/fieldManager/scope='+this.scope+'">' + this.getLanguage().translate(this.scope, 'scopeNamesPlural') + '</a> » ' + this.field);
			} else {
				$('#fields-header').show().html(this.getLanguage().translate(this.scope, 'scopeNamesPlural'));
			}
		},

		updatePageTitle: function () {
			this.setPageTitle(this.getLanguage().translate('Field Manager'));
		},
	});
});


