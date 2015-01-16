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

describe("Metadata", function () {		
	var metadata;
	
	Espo.Metadata.prototype.key = 'test';
	
	beforeEach(function () {	
		Espo.Metadata.prototype.load = function () {};		
		metadata = new Espo.Metadata();
		metadata.data = {
			recordDefs: {
				Lead: {
					some: {type: 'varchar'},
				}
			},
		};
	});
		
	
	it('#updateData should work correctly', function () {		
		metadata.updateData('recordDefs/Lead', {
			some: {type: 'text'}
		});
		expect(metadata.data.recordDefs.Lead.some.type).toBe('text');
		
		metadata.updateData('recordDefs/Contact', {
			some: {type: 'text'}
		});		
		expect(metadata.data.recordDefs.Contact.some.type).toBe('text');
	});	
	
	it('#get should work correctly', function () {
		expect(metadata.get('recordDefs/Lead/some')).toBe(metadata.data.recordDefs.Lead.some);
		
		expect(metadata.get('recordDefs/Contact')).toBe(null);
	});


});
