var Espo = Espo || {};

describe("Cache", function () {		
	var cache;
	
	beforeEach(function () {		
		cache = new Espo.Cache();		
	});
	
	it('should have \'espo-cache\' prefix', function () {
		expect(cache._prefix).toBe('espo-cache');
	});	


});
