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

Espo.define('Views.Fields.Email', 'Views.Fields.Base', function (Dep) {

	return Dep.extend({

		type: 'email',
		
		editTemplate: 'fields.email.edit',
		
		detailTemplate: 'fields.email.detail',
		
		listTemplate: 'fields.email.detail',
		
		validations: ['required', 'email'],		
	
		validateEmail: function () {
			if (this.model.get(this.name)) {		
				var re = /\S+@+\S+/;
				if (!re.test(this.model.get(this.name))) {				
					var msg = this.translate('fieldShouldBeEmail', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
					this.showValidationMessage(msg);
					return true;
				}
			}
		},
		
		events: {
			'click [data-action="mailTo"]': function (e) {
				this.mailTo($(e.currentTarget).data('email-address'));
			},
		},
		
		mailTo: function (emailAddress) {
			this.notify('Loading...');
			this.getModelFactory().create('Email', function (model) {
				this.createView('quickCreate', 'ComposeEmail', {
					attributes: {
						status: 'Draft',
						to: emailAddress
					},
				}, function (view) {
					view.render();
					view.notify(false);
				});
			}.bind(this));
		},
		
		fetch: function () {
			var data = {};
			data[this.name] = this.$element.val() || null;
			return data;
		},
		
		fetchSearch: function () {
			var value = this.$element.val() || null;
			if (value) {
				value += '%';
				var data = {
					type: 'like',
					value: value,
				};
				return data;
			}
			return false;				
		},
	});
});

