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
Espo.define('Views.Email.Fields.SelectTemplate', 'Views.Fields.Link', function (Dep) {

	return Dep.extend({

		type: 'link',
		
		foreignScope: 'EmailTemplate',
		
		editTemplate: 'email.fields.select-template.edit',
		
		setup: function () {
			Dep.prototype.setup.call(this);

			this.on('change', function () {
				var id = this.model.get(this.idName);
				if (id) {
					this.loadTemplate(id);
				}
			}, this);
		},
		
		loadTemplate: function (id) {
			var to = this.model.get('to') || '';
			var emailAddress = null;
			to = to.trim();			
			if (to) {			
				var emailAddress = to.split(';')[0].trim();
			}
			
			$.ajax({
				url: 'EmailTemplate/action/parse',				
				data: {
					id: id,
					emailAddress: emailAddress,
					parentType: this.model.get('parentType'),
					parentId: this.model.get('parentId'),
				},
				success: function (data) {
					this.model.set('name', data.subject);
					this.model.set('body', data.body);
					this.model.set({
						attachmentsIds: data.attachmentsIds,
						attachmentsNames: data.attachmentsNames
					});
					this.emptyField();
				}.bind(this),
				error: function () {
					this.emptyField();
				}.bind(this)
			});
		},
		
		emptyField: function () {
			this.model.set(this.idName, null);
			this.model.set(this.nameName, '');
		},
		
	});
	
});
