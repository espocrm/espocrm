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

describe("Controller", function () {
	var controller;
	var viewFactory;
	var view;
	
	beforeEach(function () {
		viewFactory = {
			create: {},
		};
		view = {
			render: {},
			setView: {},
		};
				
		controller = new Espo.Controller({}, {viewFactory: viewFactory});
		spyOn(viewFactory, 'create').andReturn(view);
		spyOn(view, 'render');
		spyOn(view, 'setView');
	});
	
	it ('#set should set param', function () {
		controller.set('some', 'test');		
		expect(controller.params['some']).toBe('test');
	});
	
	it ('#get should get param', function () {
		controller.set('some', 'test');
		expect(controller.get('some')).toBe('test');
	});	
	
	it ("different controllers should use same param set", function () {
		var someController = new Espo.Controller(controller.params, {viewFactory: viewFactory});		
		someController.set('some', 'test');		
		expect(controller.get('some')).toBe(someController.get('some'));		
	});
	
});
