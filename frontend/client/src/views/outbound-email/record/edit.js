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

Espo.define('Views.OutboundEmail.Record.Edit', 'Views.Record.Edit', function (Dep) {
	
	return Dep.extend({
		
		sideView: null,
		
		afterRender: function () {
			Dep.prototype.afterRender.call(this);
			
			if (!this.model.get('auth')) {
				this.hideField('username');
				this.hideField('password');	
			}
			
			var authField = this.getFieldView('auth');
			this.listenTo(authField, 'change', function () {
				var auth = authField.fetch()['auth'];					
				if (auth) {
					this.showField('username');
					this.showField('password');
				} else {
					this.hideField('username');
					this.hideField('password');
				}
			}.bind(this));
			
			var securityField = this.getFieldView('security');
			this.listenTo(securityField, 'change', function () {
				var security = securityField.fetch()['security'];
				if (['SSL', 'TLS'].indexOf(security) != -1) {
					this.model.set('port', '465');
				} else {
					this.model.set('port', '25');
				}			
			}.bind(this));
														
		},	
		
	});

});
