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
	
Espo.define('Crm:Views.Call.List', 'Views.List', function (Dep) {

	return Dep.extend({
	
		actionSetHeld: function (data) {
			var id = data.id;
			if (!id) {
				return;
			}			
			var model = this.collection.get(id);
			if (!model) {
				return;
			}
			
			model.set('status', 'Held');
			
			this.listenToOnce(model, 'sync', function () {
				this.notify(false);
				this.collection.fetch();
			}, this);
			
			this.notify('Saving...');
			model.save();		
			
		},
	
		actionSetNotHeld: function (data) {
			var id = data.id;
			if (!id) {
				return;
			}			
			var model = this.collection.get(id);
			if (!model) {
				return;
			}
			
			model.set('status', 'Not Held');
			
			this.listenToOnce(model, 'sync', function () {
				this.notify(false);
				this.collection.fetch();
			}, this);
			
			this.notify('Saving...');
			model.save();		
			
		},
	
	});
	
});
