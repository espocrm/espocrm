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

Espo.define('Views.Admin.Integrations.Index', 'View', function (Dep) {

	return Dep.extend({

		template: 'admin.integrations.index',

		integrationList: null,

		integration: null,

		data: function () {
			return {
				integrationList: this.integrationList,
				integration: this.integration,
			};
		},

		events: {
			'click #integrations-menu a.integration-link': function (e) {
				var name = $(e.currentTarget).data('name');
				this.openIntegration(name);
			},
		},

		setup: function () {			
			this.integrationList = Object.keys(this.getMetadata().get('integrations') || {});;

			this.integration = this.options.integration || null;
			
			this.on('after:render', function () {				
				this.renderHeader();				
				if (!this.integration) {
					this.renderDefaultPage();
				} else {
					this.openIntegration(this.integration);
				}				
			});			
		},

		openIntegration: function (integration) {
			this.integration = integration;
			
			this.getRouter().navigate('#Admin/integrations/name=' + integration, {trigger: false});
			
			var viewName = 'Admin.Integrations.' + this.getMetadata().get('integrations.' + integration + '.authMethod');
			this.notify('Loading...');			
			this.createView('content', viewName, {
				el: '#integration-content',
				integration: integration,
			}, function (view) {
				this.renderHeader();
				view.render();
				this.notify(false);				
				$(window).scrollTop(0);
			}.bind(this));
		},		

		renderDefaultPage: function () {
			$('#integration-header').html('').hide();
			$('#integration-content').html(this.translate('selectIntegration', 'messages', 'Integration'));
		},

		renderHeader: function () {
			if (!this.integration) {
				$('#integration-header').html('');
				return;
			}
			$('#integration-header').show().html(this.integration);
		},

		updatePageTitle: function () {
			this.setPageTitle(this.getLanguage().translate('Integrations', 'labels', 'Admin'));
		},
	});
});


