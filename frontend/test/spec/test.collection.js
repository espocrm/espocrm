/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

describe('collection', function () {
	var collection;

	beforeEach(function (done) {
		require(['collection'], function (Collection) {
			collection = new Collection();
			collection.maxSize = 5;

			spyOn(collection, 'fetch').and.returnValue(true);

			done();
		});
	});

	populate = function () {
		collection.add([
			{name: '1'},
			{name: '2'},
			{name: '3'},
			{name: '4'},
			{name: '5'},
		]);
		collection.total = 15;
	},

	it ('#sort should set sort params', function () {
		collection.sort('test', true);
		expect(collection.sortBy).toBe('test');
		expect(collection.asc).toBe(true);
	});

	it ('#nextPage and #previousPage should change offset to the next and previous pages', function () {
		collection.total = 16;

		collection.nextPage();
		expect(collection.offset).toBe(5);

		collection.nextPage();
		collection.previousPage();
		expect(collection.offset).toBe(5);

		collection.previousPage();
		expect(collection.offset).toBe(0);
	});

	it ('#firstPage and #lastPage should change offset appropriate way', function () {
		collection.total = 16;

		collection.firstPage();
		expect(collection.offset).toBe(0);

		collection.lastPage();
		expect(collection.offset).toBe(15);
	});


});
