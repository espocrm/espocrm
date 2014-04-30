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

Espo.define('Crm:Views.InboundEmail.Fields.Folder', 'Views.Fields.Base', function (Dep) {

	return Dep.extend({
		
		editTemplate: 'crm:inbound-email.fields.folder.edit',
		
		events: {
			'click [data-action="selectFolder"]': function () {			
				var self = this;
				
				this.notify('Please wait...');
				
				$.ajax({
					type: 'GET',
					url: 'InboundEmail/action/getFolders',
					data: {
						host: this.model.get('host'),
						port: this.model.get('port'),
						ssl: this.model.get('ssl'),
						username: this.model.get('username'),
						password: this.model.get('password'),
					},
					error: function () {
						Espo.Ui.error(self.translate('couldNotConnectToImap', 'messages', 'InboundEmail'));
					},
				}).done(function (folders) {
					this.createView('modal', 'Crm:InboundEmail.Modals.SelectFolder', {
						folders: folders						
					}, function (view) {
						self.notify(false);
						view.render();
						
						self.listenToOnce(view, 'select', function (folder) {							
							view.close();
							self.addFolder(folder);							
						});
					});
				}.bind(this));				
			}	
		},
		
		addFolder: function (folder) {
			this.$element.val(folder);
		},
	});	
});
