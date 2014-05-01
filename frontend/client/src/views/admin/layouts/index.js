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

Espo.define('Views.Admin.Layouts.Index', 'View', function (Dep) {

	return Dep.extend({

		template: 'admin.layouts.index',

		scopeList: null,

		typeList: ['list', 'detail', 'listSmall', 'detailSmall', 'filters', 'massUpdate', 'relationships'],

		scope: null,

		type: null,

		data: function () {
			return {
				scopeList: this.scopeList,
				typeList: this.typeList,
				scope: this.scope,
			};
		},

		events: {
			'click #layouts-menu button.layout-link': function (e) {
				var scope = $(e.currentTarget).data('scope');
				var type = $(e.currentTarget).data('type');
				if (this.getView('content')) {
					if (this.scope == scope && this.type == type) {
						return;
					}
				}
				$("#layouts-menu button.layout-link").removeClass('disabled');
				$(e.target).addClass('disabled');
				this.openLayout(scope, type);
			},
		},

		setup: function () {
			this.scopeList = [];				
			var scopesAll = Object.keys(this.getMetadata().get('scopes'));;
			scopesAll.forEach(function (scope) {
				if (this.getMetadata().get('scopes.' + scope + '.entity') &&
				    this.getMetadata().get('scopes.' + scope + '.layouts')) {
					this.scopeList.push(scope);
				}
			}.bind(this));	

			this.on('after:render', function () {
				$("#layouts-menu button[data-scope='" + this.options.scope + "'][data-type='" + this.options.type + "']").addClass('disabled');
				this.renderLayoutHeader();
				if (!this.options.scope) {
					this.renderDefaultPage();
				}
				if (this.scope) {
					this.openLayout(this.options.scope, this.options.type);
				}
			});

			this.scope = this.options.scope || null;
			this.type = this.options.type || null;
		},

		openLayout: function (scope, type) {
			this.scope = scope;
			this.type = type;

			this.getRouter().navigate('#Admin/layouts/scope=' + scope + '&type=' + type, {trigger: false});

			this.notify('Loading...');

			this.createView('content', 'Admin.Layouts.' + Espo.Utils.upperCaseFirst(type), {
				el: '#layout-content',
				scope: scope,
				type: type,
			}, function (view) {
				this.renderLayoutHeader();
				view.render();
				this.notify(false);
				$(window).scrollTop(0);
			}.bind(this));
		},

		renderDefaultPage: function () {
			$("#layout-header").html("").hide();
			$("#layout-content").html("Select needed layout in the left menu and edit it.");
		},

		renderLayoutHeader: function () {
			if (!this.scope) {
				$("#layout-header").html("");
				return;
			}
			$("#layout-header").show().html(this.getLanguage().translate(this.scope, 'scopeNamesPlural') + " » " + this.getLanguage().translate(this.type, 'layouts', 'Admin'));
		},

		updatePageTitle: function () {
			this.setPageTitle(this.getLanguage().translate('Layout Manager'));
		},
	});
});


