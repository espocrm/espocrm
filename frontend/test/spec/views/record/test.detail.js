
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
