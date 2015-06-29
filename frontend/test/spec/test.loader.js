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

describe("Loader", function () {
	var loader;

	beforeEach(function () {
		loader = new Espo.Loader();
		Espo.Testing = 'test';
	});

	afterEach(function () {
		delete Espo.Testing;
	});



	it("should convert name to path", function () {
		expect(loader._nameToPath('Views.Record.Edit')).toBe('client/src/views/record/edit.js');
		expect(loader._nameToPath('Views.Home.DashletHeader')).toBe('client/src/views/home/dashlet-header.js');
	});


});
