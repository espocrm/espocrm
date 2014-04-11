describe("Views.Fields.Email", function () {
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
				email: {
					required: true,
					type: 'email',
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
	
		field = new Espo['Views.Fields.Email']({
			model: model,			
			defs: {
				name: 'email',
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
		model.set('email', '');		
		expect(field.validate()).toBe(true);
		model.set('email', 'false');		
		expect(field.validateRequired() || false).toBe(false);
		
		model.set('email', '');	
		model.defs.fields.email.required = false;
		expect(field.validate() || false).toBe(false);		
	});
	
	it("should validate email", function () {
		model.set('email', 'test@email.com');
		expect(field.validate() || false).toBe(false);
		
		model.set('email', 'testemail.com');
		expect(field.validate()).toBe(true);	
	});


});
