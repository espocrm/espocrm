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
