describe("Views.Fields.Base", function () {
	var model;
	
	var templator = {
		getTemplate: {}
	};
	
	beforeEach(function () {
		model = new Espo.Model();
		spyOn(templator, 'getTemplate').andReturn('');
	});

	
	it("should validate required", function () {
	
		model.defs = {
			fields: {
				name: {
					required: true,
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
	
		var field = new Espo['Views.Fields.Base']({
			model: model,			
			defs: {
				name: 'name',
			},
			templator: templator,
			helper: {
				language: {
					translate: function () {}
				},
				fieldManager: fieldManager
			},
		});
		
		model.set('name', '');
		
		expect(field.validate()).toBe(true);
	});


});
