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

Espo.define('Crm:Views.Task.Fields.DateEnd', 'Views.Fields.Datetime', function (Dep) {

	return Dep.extend({
	
		detailTemplate: 'crm:task.fields.date-end.detail',
		
		listTemplate: 'crm:task.fields.date-end.detail',
		
		data: function () {
			var data = Dep.prototype.data.call(this);
			
			if (!~['Completed', 'Canceled'].indexOf(this.model.get('status'))) {
				if (this.mode == 'list' || this.mode == 'detail') {
					var value = this.model.get(this.name);	
					if (value) {			
						var d = this.getDateTime().toMoment(value);
						var now = moment().tz(this.getDateTime().timeZone || 'UTC');
						if (d.unix() < now.unix()) {
							data.isOverdue = true;
						}
					}
				}
			}
			
			return data;
		},
		
	});
});

