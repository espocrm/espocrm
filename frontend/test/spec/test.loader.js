var Espo = Espo || {};

describe("Loader", function () {		
	var loader;
	
	beforeEach(function () {		
		loader = new Espo.Loader();
		Espo.Testing = 'test';
	});
	
	afterEach(function () {
		delete Espo.Testing;
	});
	
		
	
	it("should convert name to path", function () {	
		expect(loader._nameToPath('Views.Record.Edit')).toBe('src/views/record/edit.js');		
		expect(loader._nameToPath('Views.Home.DashletHeader')).toBe('src/views/home/dashlet-header.js');
	});	
	
	
});
