var Espo = Espo || {};

describe("Utils", function () {
		
	beforeEach(function () {
	});
	
	it('#upperCaseFirst should make first letter upercase', function () {
		expect(Espo.Utils.upperCaseFirst('someTest')).toBe('SomeTest');
	});

});
