/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
var Espo = Espo || {};

describe("DateTime", function () {
	var dateTime;

	beforeEach(function () {
		dateTime = new Espo.DateTime();
	});

	it("should convert date from display format", function () {
		dateTime.dateFormat = 'MM/DD/YYYY';
		expect(dateTime.fromDisplayDate('10/05/2013')).toBe('2013-10-05');

		dateTime.dateFormat = 'DD.MM.YYYY';
		expect(dateTime.fromDisplayDate('05.10.2013')).toBe('2013-10-05');

		dateTime.dateFormat = 'YYYY-MM-DD';
		expect(dateTime.fromDisplayDate('2013-10-05')).toBe('2013-10-05');
	});

	it("should convert date to display format", function () {
		dateTime.dateFormat = 'MM/DD/YYYY';
		expect(dateTime.toDisplayDate('2013-10-05')).toBe('10/05/2013');

		dateTime.dateFormat = 'DD.MM.YYYY';
		expect(dateTime.toDisplayDate('2013-10-05')).toBe('05.10.2013');

		dateTime.dateFormat = 'YYYY-MM-DD';
		expect(dateTime.toDisplayDate('2013-10-05')).toBe('2013-10-05');
	});

	it("should convert date/time from display format and consider timezone", function () {
		dateTime.dateFormat = 'MM/DD/YYYY';
		dateTime.timeFormat = 'hh:mm A';
		expect(dateTime.fromDisplay('10/05/2013 02:45 PM')).toBe('2013-10-05 14:45:00');

		dateTime.dateFormat = 'MM/DD/YYYY';
		dateTime.timeFormat = 'hh:mm a';
		expect(dateTime.fromDisplay('10/05/2013 02:45 pm')).toBe('2013-10-05 14:45:00');

		dateTime.dateFormat = 'DD.MM.YYYY';
		dateTime.timeFormat = 'HH:mm';
		expect(dateTime.fromDisplay('05.10.2013 14:45')).toBe('2013-10-05 14:45:00');

		dateTime.timeZone = 'Europe/Kiev';
		expect(dateTime.fromDisplay('05.10.2013 17:45')).toBe('2013-10-05 14:45:00');
	});

	it("should convert date/time to display format and consider timezone", function () {
		dateTime.dateFormat = 'MM/DD/YYYY';
		dateTime.timeFormat = 'hh:mm A';
		expect(dateTime.toDisplay('2013-10-05 14:45')).toBe('10/05/2013 02:45 PM');

		dateTime.dateFormat = 'MM/DD/YYYY';
		dateTime.timeFormat = 'hh:mm a';
		expect(dateTime.toDisplay('2013-10-05 14:45')).toBe('10/05/2013 02:45 pm');

		dateTime.dateFormat = 'DD.MM.YYYY';
		dateTime.timeFormat = 'HH:mm';
		expect(dateTime.toDisplay('2013-10-05 14:45')).toBe('05.10.2013 14:45');

		dateTime.timeZone = 'Europe/Kiev';
		expect(dateTime.toDisplay('2013-10-05 14:45')).toBe('05.10.2013 17:45');
	});

});
