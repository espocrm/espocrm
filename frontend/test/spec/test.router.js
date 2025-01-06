/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

describe('router', () => {
    let router;

    beforeEach(done => {
		require('router', Router => {
			router = new Router();
			done();
		});
	});

	it('should parse slashed options', () => {
        let options = router._parseOptionsParams("p=1&t=2");
        expect(options.p).toBe('1');
		expect(options.t).toBe('2');

        options = router._parseOptionsParams("p=1&");
        expect(options.p).toBe('1');

        options = router._parseOptionsParams("p");
        expect(options).toBe("p");

        options = router._parseOptionsParams("p=1&t");
        expect(options.p).toBe('1');
		expect(options.t).toBe(true);

        options = router._parseOptionsParams("p=1&t=");
        expect(options.t).toBe('');
	});
});
