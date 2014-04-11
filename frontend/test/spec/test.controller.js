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
