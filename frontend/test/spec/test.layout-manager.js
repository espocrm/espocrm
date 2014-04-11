var Espo = Espo || {};

describe("LayoutManager", function () {		
	var layoutManager;
	
	beforeEach(function () {		
		layoutManager = new Espo.LayoutManager();	
		spyOn(layoutManager, 'ajax').andCallFake(function (options) {
		});	
	});
	
	
	it("should call ajax to fetch new layout", function () {
		layoutManager.get('some', 'list');
		
		expect(layoutManager.ajax.mostRecentCall.args[0].url).toBe('some/layout/list');
		
		

		
	});
	
	
});
