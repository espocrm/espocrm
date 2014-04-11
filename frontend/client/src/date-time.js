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

Espo.DateTime = function () {

};

_.extend(Espo.DateTime.prototype, {

	internalDateFormat: 'YYYY-MM-DD',

	internalDateTimeFormat: 'YYYY-MM-DD HH:mm',

	dateFormat: 'MM/DD/YYYY',

	timeFormat: 'HH:mm',

	timeZone: null,

	weekStart: 1,

	hasMeridian: function () {
		return (new RegExp('A', 'i')).test(this.timeFormat);
	},
	
	getDateFormat: function () {
		return this.dateFormat;
	},

	getDateTimeFormat: function () {
		return this.dateFormat + ' ' + this.timeFormat;
	},

	/*fromTimestamp: function (ts) {
		var m = moment.unix(ts);
		if (this.timeZone) {
			m = moment.tz(m, this.timeZone).utc();
		}
		return m.format(this.internalDateTimeFormat);
	},*/

	fromDisplayDate: function (string) {
		if (!string) {
			return null;
		}
		var m = moment(string, this.dateFormat);
		if (!m.isValid()) {
			return -1;
		}
		return m.format(this.internalDateFormat);
	},

	toDisplayDate: function (string) {
		
		if (!string || (typeof string != 'string')) {
			return '';
		}
		
		var m = moment(string, this.internalDateFormat);
		if (!m.isValid()) {
			return '';
		}
		
		return m.format(this.dateFormat);
	},

	fromDisplay: function (string) {
		if (!string) {
			return null;
		}		
		var m;
		if (this.timeZone) {	
			m = moment.tz(string, this.getDateTimeFormat(), this.timeZone).utc();
		} else {
			m = moment.utc(string, this.getDateTimeFormat());
		}
		
		if (!m.isValid()) {
			return -1;
		}
		return m.format(this.internalDateTimeFormat) + ':00';
	},

	toDisplay: function (string) {
		if (!string) {
			return '';
		}
		return this.toMoment(string).format(this.getDateTimeFormat());
	},

	toMoment: function (string) {
		var m = moment.utc(string, this.internalDateTimeFormat);
		if (this.timeZone) {
			m = m.tz(this.timeZone);
		}
		return m;
	},

	fromIso: function (string) {
		if (!string) {
			return '';
		}
		var m = moment(string).utc();
		return m.format(this.internalDateTimeFormat);
	},

	toIso: function (string) {
		if (!string) {
			return null;
		}
		return this.toMoment(string).format();
	},

	getToday: function () {
		return moment.utc().format(this.internalDateFormat);
	},

	getNow: function (multiplicity) {
		if (!multiplicity) {
			return moment.utc().format(this.internalDateTimeFormat);
		} else {
			var unix = moment().unix();
			unix = unix - (unix % (multiplicity * 60));
			return moment.unix(unix).utc().format(this.internalDateTimeFormat);
		}
	},

	setSettingsAndPreferences: function (settings, preferences) {
		
		if (settings.has('dateFormat')) {
			this.dateFormat = settings.get('dateFormat');
		}
		if (settings.has('timeFormat')) {
			this.timeFormat = settings.get('timeFormat');
		}
		if (settings.has('timeZone')) {
			this.timeZone = settings.get('timeZone') || null;
			if (this.timeZone == 'UTC') {
				this.timeZone = null;
			}
		}
		if (settings.has('weekStart')) {
			this.weekStart = settings.get('weekStart');
		}
		
		preferences.on('change', function (model) {
			this.dateFormat = model.get('dateFormat');
			this.timeFormat = model.get('timeFormat');
			this.timeZone = model.get('timeZone');
			this.weekStart = model.get('weekStart');
			if (this.timeZone == 'UTC') {
				this.timeZone = null;
			}	
		}, this);
	},
});

