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

Espo.define('Views.Fields.DatetimeShort', 'Views.Fields.Datetime', function (Dep) {

	return Dep.extend({

		getValueForDisplay: function () {
		
			if (this.mode == 'list' || this.mode == 'detail') {
				var value = this.model.get(this.name);				
				if (value) {
					var string;
				
					var d = this.getDateTime().toMoment(value);
				
					var now = moment().tz(this.getDateTime().timeZone);
				
					if (d.unix() > now.clone().startOf('day').unix() && d.unix() < now.clone().add('days', 1).startOf('day').unix()) {
						string = d.format(this.getDateTime().timeFormat);
						return string;
					}				
				
					if (d.format('YYYY') == now.format('YYYY')) {
						string = d.format('MMM D');
					} else {
						string = d.format('MMM D, YY');
					}
				
				
					return string;
				}
			}
			
			return Dep.prototype.getValueForDisplay.call(this);
		},


		
	});
});

