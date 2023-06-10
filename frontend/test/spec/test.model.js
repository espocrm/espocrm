/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

describe('model', () => {
	let model;

	beforeEach(done => {
		require(['model', 'utils'], ModelBase => {
			const Model = class extends ModelBase {};

			model = new Model({}, {
                entityType: 'some',
                defs: {
                    fields: {
                        'id': {
                        },
                        'name': {
                            maxLength: 150,
                            required: true,
                        },
                        'email': {
                            type: 'email',
                        },
                        'phone': {
                            maxLength: 50,
                            default: '007'
                        },
                    },
                },
            });

			done();
		});
	});

	it ('should set urlRoot as name', () => {
		expect(model.urlRoot).toBe('some');
	});

	it ('#getFieldType should return field type or null if undefined', () => {
		expect(model.getFieldType('email')).toBe('email');
		expect(model.getFieldType('name')).toBe(null);
	});

	it ('#isRequired should return true if field is required and false if not', () => {
		expect(model.isRequired('name')).toBe(true);
		expect(model.isRequired('email')).toBe(false);
	});

	it ('should set defaults correctly', () => {
		model.populateDefaults();
		expect(model.get('phone')).toBe('007');
	});
});
