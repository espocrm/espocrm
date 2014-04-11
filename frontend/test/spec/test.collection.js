var Espo = Espo || {};


describe("Collection", function () {
	var collection;
	
	beforeEach(function () {
		collection = new Espo.Collection();
		collection.maxSize = 5;
		
		spyOn(collection, 'fetch').andReturn(true);		
	});
	
	populate = function () {
		collection.add([
			{name: '1'},
			{name: '2'},
			{name: '3'},
			{name: '4'},
			{name: '5'},
		]);		
		collection.total = 15;
	},	
	
	it ('#sort should set sort params', function () {
		collection.sort('test', true);		
		expect(collection.sortBy).toBe('test');
		expect(collection.asc).toBe(true);		
	});
	
	it ('#nextPage and #previousPage should change offset to the next and previous pages', function () {		
		collection.total = 16;
		
		collection.nextPage();
		expect(collection.offset).toBe(5);
		
		collection.nextPage();		
		collection.previousPage();
		expect(collection.offset).toBe(5);
		
		collection.previousPage();
		expect(collection.offset).toBe(0);		
	});	
	
	it ('#firstPage and #lastPage should change offset appropriate way', function () {	
		collection.total = 16;
		
		collection.firstPage();
		expect(collection.offset).toBe(0);
		
		collection.lastPage();
		expect(collection.offset).toBe(15);			
	});

});
