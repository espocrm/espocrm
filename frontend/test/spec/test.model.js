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

describe('model', () => {
	let model;

	beforeEach(done => {
		Espo.loader.require(['model', 'utils'], ModelBase => {
			const Model = class extends ModelBase {};

			model = new Model({}, {
                entityType: 'Some',
                defs: {
                    fields: {
                        'id': {
                            type: 'id',
                        },
                        'name': {
                            maxLength: 150,
                            required: true,
                        },
                        'email': {
                            type: 'email',
                        },
                        'phone': {
                            type: 'phone',
                            maxLength: 50,
                            default: '007',
                        },
                    },
                },
            });

			done();
		});
	});

	it('should set urlRoot as name', () => {
		expect(model.urlRoot).toBe('Some');
	});

	it('#getFieldType should return field type or null if undefined', () => {
		expect(model.getFieldType('email')).toBe('email');
		expect(model.getFieldType('name')).toBe(null);
	});

	it('#isRequired should return true if field is required and false if not', () => {
		expect(model.isRequired('name')).toBe(true);
		expect(model.isRequired('email')).toBe(false);
	});

	it('should set defaults correctly', () => {
		model.populateDefaults();
		expect(model.get('phone')).toBe('007');
	});

    it('should fire change event on set 1', () => {
        return new Promise(resolve => {
            model.once('change', () => {
                expect(model.get('name')).toBe('test');

                resolve();
            });

            model.set('name', 'test');
        });
    });

    it('should fire change event on set 2', () => {
        return new Promise(resolve => {
            model.once('change:name', (m, v) => {
                expect(model.get('name')).toBe('test');
                expect(v).toBe('test');

                resolve();
            });

            model.set({'name': 'test'});
        });
    });

    it('should not fire change event on set with silent option', () => {
        return new Promise((resolve, reject) => {
            model.once('change:name', () => {
                reject();
            });

            model.once('change:hello', (m, v, o) => {
                expect(o.test).toBe(true);

                resolve();
            });

            model.setMultiple({'name': 'test'}, {silent: true});
            model.setMultiple({'hello': 'hello'}, {test: true});
        });
    });

    it('should unset attribute', () => {
        model.set('name', 'test');

        expect(model.has('name')).toBeTrue();

        model.unset('name');

        expect(model.has('name')).toBeFalse();
    });

    it('should unset attribute and fire events', () => {
        return new Promise((resolve, reject) => {
            model.setMultiple({
                'name': '1',
                'hello': 1,
            });

            model.once('change:name', () => {
                reject();
            });

            model.once('change:hello', (m, v, o) => {
                expect(o.test).toBe(true);

                resolve();
            });

            model.unset('name', {silent: true});
            model.unset('hello', {test: true});
        });
    });

    it('should clear attributes', () => {
        model.set('name', 'test');

        expect(model.has('name')).toBeTrue();

        model.clear();

        expect(model.has('name')).toBeFalse();
    });

    it('should update with PUT', () => {
        model.id = '000';
        model.set('test1', '1');
        model.set('test2', '2');

        return new Promise(resolve => {
            model.on('request', (url, method, data) => {
                expect(url).toBe('Some/000');
                expect(method).toBe('PUT');
                expect(data).toEqual({
                    test1: '1',
                    test2: '2',
                    test3: '3',
                });

                resolve();
            });

            model.save({test3: '3'}, {bypassRequest: true});
        });
    });

    it('should patch with PUT', () => {
        model.id = '000';
        model.set('test1', '1');
        model.set('test2', '2');

        return new Promise(resolve => {
            model.on('request', (url, method, data) => {
                expect(url).toBe('Some/000');
                expect(method).toBe('PUT');
                expect(data).toEqual({
                    test3: '3',
                });

                resolve();
            });

            model.save({test3: '3'}, {bypassRequest: true, patch: true});
        });
    });

    it('should create with POST', () => {
        model.set('test1', '1');
        model.set('test2', '2');

        return new Promise(resolve => {
            model.on('request', (url, method, data) => {
                expect(url).toBe('Some');
                expect(method).toBe('POST');
                expect(data).toEqual({
                    test1: '1',
                    test2: '2',
                    test3: '3',
                });

                resolve();
            });

            model.save({test3: '3'}, {bypassRequest: true});
        });
    });

    it('should destroy with DELETE', () => {
        model.id = '000';
        model.set('test1', '1');
        model.set('test2', '2');

        return new Promise(resolve => {
            model.on('request', (url, method) => {
                expect(url).toBe('Some/000');
                expect(method).toBe('DELETE');

                resolve();
            });

            model.destroy({bypassRequest: true});
        });
    });
});
