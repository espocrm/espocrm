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

describe("Views.Record.Detail", function () {
	var detail;
	var layoutManager = {
		get: {}
	};
	var factory = {
		create: {},
	};


	beforeEach(function () {
		var view = new Espo.View();
		var model = new Espo.Model();
		model.name = 'Test';

		var mockUser = {
			isAdmin: function () {
				return true;
			}
		}

		spyOn(factory, 'create');
		spyOn(layoutManager, 'get').andCallFake(function (n, p, callback) {
			callback([
				{
					label: 'overview',
					rows: [
						[
							{
								name: 'name',
							},
							{
								name: 'email',
								type: 'base',
							},
						],
						[
							{
								name: 'phone',
								customLabel: 'yo',
								params: {},
							}
						]
					]
				}
			]);
		});
		detail = new Espo['Views.Record.Detail']({
			model: model,
			helper: {
				layoutManager: layoutManager,
				user: mockUser,
				language: {
					translate: function () {},
				},
				fieldManager: {
					getViewName: function (name) {
						return 'Fields.' + Espo.Utils.upperCaseFirst(name);
					},
				},
				metadata: {
					get: function () {

					},
				},
			},
			factory: factory
		});
	});

	it ('should create record view with a proper layout', function () {
		expect(factory.create.calls.length).toBe(3);
		var arg = factory.create.calls[0].args[1];
		expect(arg._layout.layout[0].rows[0][1].name).toBe('email');
	});
});
