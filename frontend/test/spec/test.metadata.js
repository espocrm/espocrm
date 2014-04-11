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
