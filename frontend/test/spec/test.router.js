/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
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

describe('router', function () {
	var router;

	beforeEach(function (done) {
		require('router', function (Router) {
			router = new Router();
			done();
		});
	});

	it('should parse slashed options', function () {
		var options = router._parseOptionsParams("p=1&t=2");
		expect(options.p).toBe('1');
		expect(options.t).toBe('2');

		var options = router._parseOptionsParams("p=1&");
		expect(options.p).toBe('1');

		var options = router._parseOptionsParams("p");
		expect(options).toBe("p");

		var options = router._parseOptionsParams("p=1&t");
		expect(options.p).toBe('1');
		expect(options.t).toBe(true);

		var options = router._parseOptionsParams("p=1&t=");
		expect(options.t).toBe('');
	});

});
