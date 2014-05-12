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
	
Espo.define('Views.Preferences.Record.Edit', 'Views.Record.Edit', function (Dep) {		

	return Dep.extend({
	
		sideView: null,
	
		buttons: [
			{
				name: 'save',
				label: 'Save',
				style: 'primary',
			},
			{
				name: 'cancel',
				label: 'Cancel',
			}			
		],
		
		setup: function () {
			Dep.prototype.setup.call(this);
			
			if (this.model.id == this.getUser().id) {
				this.on('after:save', function () {
					this.getPreferences().set(this.model.toJSON());
					this.getPreferences().trigger('update');
				}, this);
			}
		},
		
		afterRender: function () {
			Dep.prototype.afterRender.call(this);
			
			if (!this.model.get('smtpAuth')) {
				this.hideField('smtpUsername');
				this.hideField('smtpPassword');	
			}
			
			var smtpAuthField = this.getFieldView('smtpAuth');
			this.listenTo(smtpAuthField, 'change', function () {
				var smtpAuth = smtpAuthField.fetch()['smtpAuth'];					
				if (smtpAuth) {
					this.showField('smtpUsername');
					this.showField('smtpPassword');
				} else {
					this.hideField('smtpUsername');
					this.hideField('smtpPassword');
				}
			}.bind(this));
			
			var smtpSecurityField = this.getFieldView('smtpSecurity');
			this.listenTo(smtpSecurityField, 'change', function () {
				var smtpSecurity = smtpSecurityField.fetch()['smtpSecurity'];
				if (smtpSecurity == 'SSL') {
					this.model.set('smtpPort', '465');
				} else if (smtpSecurity == 'TLS') {
					this.model.set('smtpPort', '587');
				} else {
					this.model.set('smtpPort', '25');
				}			
			}.bind(this));														
		},
		
		exit: function (after) {
			if (after == 'cancel') {
				this.getRouter().navigate('#User/view/' + this.model.id, {trigger: true});
			}
		},
	
	});		
	
});

