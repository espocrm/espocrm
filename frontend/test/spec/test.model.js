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


describe("Model", function () {
	var model;
	
	var Model = Espo.Model.extend({
		name: 'some',
		
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
					default: '007',
				}
			}		
		},		
	});
	
	beforeEach(function () {
		model = new Model();
	});
	
	it ('should set urlRoot as name', function () {
		expect(model.urlRoot).toBe('some');
	});
	
	it ('#getFieldType should return field type or null if undefined', function () {
		expect(model.getFieldType('email')).toBe('email');
		expect(model.getFieldType('name')).toBe(null);
	});
	
	it ('#isRequired should return true if field is required and false if not', function () {
		expect(model.isRequired('name')).toBe(true);
		expect(model.isRequired('email')).toBe(false);
	});
	
	it ('should set defaults correctly', function () {
		model.populateDefaults();
		expect(model.get('phone')).toBe('007');
	});
	
	

});
