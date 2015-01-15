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
describe("Views.Fields.Int", function () {
	var model;
	var field;
	
	var templator = {
		getTemplate: {}
	};
	
	beforeEach(function () {
		model = new Espo.Model();
		spyOn(templator, 'getTemplate').andReturn('');
		
		model.defs = {
			fields: {
				count: {
					required: true,
					type: 'int',
					min: 0,
					max: 1,
				}
			}
		};
		
		var fieldManager = {
			getParams: function () {},
			getAttributes: function () {}
		};		
		spyOn(fieldManager, 'getParams').andReturn([
			'required',			
		]);
		spyOn(fieldManager, 'getAttributes').andReturn([
			[],			
		]);	
	
		field = new Espo['Views.Fields.Int']({
			model: model,			
			defs: {
				name: 'count',
			},
			templator: templator,
			helper: {
				language: {
					translate: function () {}
				},
				fieldManager: fieldManager
			},
		});
	});

	
	it("should validate required", function () {
		model.set('count', null);		
		expect(field.validateRequired()).toBe(true);
		
		model.set('count', false);		
		expect(field.validateRequired()).toBe(true);
		
		model.set('count', 0);		
		expect(field.validateRequired() || false).toBe(false);
		
	});
	
	it("should validate range", function () {
		model.set('count', 0);		
		expect(field.validateRange() || false).toBe(false);
		
		model.set('count', -1);		
		expect(field.validateRange()).toBe(true);
		
		model.set('count', 2);		
		expect(field.validateRange()).toBe(true);
	});

});
