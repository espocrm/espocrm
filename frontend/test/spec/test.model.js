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
