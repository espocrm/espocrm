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

describe("Acl", function () {		
	var acl;
	
	beforeEach(function () {		
		acl = new Espo.Acl();	
	});	
	
	it("should check an access properly", function () {	
		acl.set({
			lead: {
				read: 'team',
				edit: 'own',
				delete: 'no',
			},
			contact: {
				read: true,
				edit: false,
				delete: false,
			},
			opportunity: false,
			meeting: true,
		});
		
		expect(acl.check('lead', 'read')).toBe(true);
		
		expect(acl.check('lead', 'read', false, false)).toBe(false);
		expect(acl.check('lead', 'read', false, true)).toBe(true);		
		expect(acl.check('lead', 'read', true)).toBe(true);		
		
		expect(acl.check('lead', 'edit')).toBe(true);
		expect(acl.check('lead', 'edit', false, true)).toBe(false);
		expect(acl.check('lead', 'edit', true, false)).toBe(true);
		
		expect(acl.check('lead', 'delete')).toBe(false);
		
		expect(acl.check('contact', 'read')).toBe(true);
		expect(acl.check('contact', 'edit')).toBe(false);
		
		expect(acl.check('lead', 'convert')).toBe(false);
		expect(acl.check('account', 'edit')).toBe(true);
		
		expect(acl.check('opportunity', 'edit')).toBe(false);
		expect(acl.check('meeting', 'edit')).toBe(true);
	});	
	
});
