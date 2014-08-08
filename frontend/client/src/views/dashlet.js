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

Espo.define('Views.Dashlet', 'View', function (Dep) {

	return Dep.extend({

		name: null,

		id: null,

		elId: null,

		template: 'dashlet',

		data: function () {
			return {
				name: this.name,
				id: this.id,
				title: this.getOption('title'),
			};
		},

		setup: function () {
			this.events = this.events || {};
			this.name = this.options.name;
			this.id = this.options.id;

			this.events['click [data-action="refresh"]'] = function (e) {
				this.actionRefresh();
			}.bind(this);
			this.events['click [data-action="options"]'] = function (e) {
				this.actionOptions();
			};
			this.events['click [data-action="remove"]'] = function (e) {
				this.actionRemove();
			};

			var bodySelector = '#dashlet-' + this.id + ' .dashlet-body';
			var view = this.getMetadata().get('dashlets.' + this.name + '.view') || 'Dashlets.' + this.name;
						
			this.createView('body', view, {el: bodySelector, id: this.id});
		},
		
		refresh: function () {
			this.getView('body').actionRefresh();
		},		

		actionRefresh: function () {
			this.refresh();
		},

		actionOptions: function () {
			var optionsView = this.getView('body').optionsView;
			this.createView('options', optionsView, {
				name: this.name,
				optionsData: this.getOptionsData(),
				fields: this.getView('body').optionsFields,
			}, function (view) {
				view.render();
			});
		},

		getOptionsData: function () {
			return this.getView('body').optionsData;
		},

		getOption: function (key) {
			return this.getView('body').getOption(key);
		},

		actionRemove: function () {
			var dashboard = this.getParentView().getParentView();
			dashboard.removeDashlet(this.options.id);
			this.$el.remove();
			this.remove();
		},
	});
});


