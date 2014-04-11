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
Espo.define('Controllers.Admin', 'Controller', function (Dep) {
	
	return Dep.extend({	
	
		checkAccess: function () {
			if (this.getUser().isAdmin()) {
				return true;
			}
			return false;
		},			
		
		index: function () {		
			this.main('Admin.Index', null);
		},
				
		layouts: function (options) {
			var scope = options.scope || null;
			var type = options.type || null;		

			this.main('Admin.Layouts.Index', {scope: scope, type: type});
		},
		
		fieldManager: function (options) {
			var scope = options.scope || null;
			var field = options.field || null;		

			this.main('Admin.FieldManager.Index', {scope: scope, field: field});
		},
		
		upgrade: function (options) {
			this.main('Admin.Upgrade.Index');
		},	
		
		getSettingsModel: function () {
			var model = this.getSettings().clone();
			model.defs = this.getSettings().defs;
		
			return model;
		},
		
		settings: function () {
			var model = this.getSettingsModel();
											
			model.once('sync', function () {
				model.id = '1';					
				this.main('Edit', {
					model: model,
					views: {
						header: {template: 'admin.settings.header'},
						body: {view: 'Admin.Settings'},
					},
				});
			}, this);				
			model.fetch();	
		},
		
		outboundEmail: function () {			
			var model = this.getSettingsModel();						
			
			model.once('sync', function () {
				model.id = '1';
				this.main('Edit', {
					model: model,
					views: {
						header: {template: 'admin.settings.header-outbound-email'},
						body: {view: 'Admin.OutboundEmail'},
					},
				});
			}, this);				
			model.fetch();
		},
		
		userInterface: function () {
			var model = this.getSettingsModel();						
			
			model.once('sync', function () {
				model.id = '1';
				this.main('Edit', {
					model: model,
					views: {
						header: {template: 'admin.settings.header-user-interface'},
						body: {view: 'Admin.UserInterface'},
					},
				});
			}, this);				
			model.fetch();
		},
		
		rebuild: function (options) {
			var master = this.get('master');		
			Espo.Ui.notify(master.translate('Please wait'));
			this.getRouter().navigate('#Admin');	
			$.ajax({
				url: 'Admin/rebuild',
				success: function () {
					var msg = master.translate('Rebuild has been done', 'labels', 'Admin');
					Espo.Ui.success(msg);					
				}.bind(this)
			});		
		},
		
		clearCache: function (options) {
			var master = this.get('master');		
			Espo.Ui.notify(master.translate('Please wait'));
			this.getRouter().navigate('#Admin');			
			$.ajax({
				url: 'Admin/clearCache',
				success: function () {
					var msg = master.translate('Cache has been cleared', 'labels', 'Admin');
					Espo.Ui.success(msg);					
				}.bind(this)
			});	
		},	
	});
	
});
