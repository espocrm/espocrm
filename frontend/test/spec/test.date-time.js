var Espo = Espo || {};

describe("DateTime", function () {		
	var dateTime;
	
	beforeEach(function () {		
		dateTime = new Espo.DateTime();	
	});	
	
	it("should convert date from display format", function () {	
		dateTime.dateFormat = 'MM/DD/YYYY';		
		expect(dateTime.fromDisplayDate('10/05/2013')).toBe('2013-10-05');
		
		dateTime.dateFormat = 'DD.MM.YYYY';	
		expect(dateTime.fromDisplayDate('05.10.2013')).toBe('2013-10-05');
		
		dateTime.dateFormat = 'YYYY-MM-DD';	
		expect(dateTime.fromDisplayDate('2013-10-05')).toBe('2013-10-05');
	});
	
	it("should convert date to display format", function () {	
		dateTime.dateFormat = 'MM/DD/YYYY';		
		expect(dateTime.toDisplayDate('2013-10-05')).toBe('10/05/2013');
		
		dateTime.dateFormat = 'DD.MM.YYYY';	
		expect(dateTime.toDisplayDate('2013-10-05')).toBe('05.10.2013');
		
		dateTime.dateFormat = 'YYYY-MM-DD';	
		expect(dateTime.toDisplayDate('2013-10-05')).toBe('2013-10-05');
	});
	
	it("should convert date/time from display format and consider timezone", function () {
		dateTime.dateFormat = 'MM/DD/YYYY';
		dateTime.timeFormat = 'hh:mm A';
		expect(dateTime.fromDisplay('10/05/2013 02:45 PM')).toBe('2013-10-05 14:45');
		
		dateTime.dateFormat = 'MM/DD/YYYY';
		dateTime.timeFormat = 'hh:mm a';
		expect(dateTime.fromDisplay('10/05/2013 02:45 pm')).toBe('2013-10-05 14:45');
		
		dateTime.dateFormat = 'DD.MM.YYYY';
		dateTime.timeFormat = 'HH:mm';
		expect(dateTime.fromDisplay('05.10.2013 14:45')).toBe('2013-10-05 14:45');
		
		dateTime.timeZone = 'Europe/Kiev';
		expect(dateTime.fromDisplay('05.10.2013 17:45')).toBe('2013-10-05 14:45');
	});
	
	it("should convert date/time to display format and consider timezone", function () {
		dateTime.dateFormat = 'MM/DD/YYYY';
		dateTime.timeFormat = 'hh:mm A';
		expect(dateTime.toDisplay('2013-10-05 14:45')).toBe('10/05/2013 02:45 PM');
		
		dateTime.dateFormat = 'MM/DD/YYYY';
		dateTime.timeFormat = 'hh:mm a';
		expect(dateTime.toDisplay('2013-10-05 14:45')).toBe('10/05/2013 02:45 pm');
		
		dateTime.dateFormat = 'DD.MM.YYYY';
		dateTime.timeFormat = 'HH:mm';
		expect(dateTime.toDisplay('2013-10-05 14:45')).toBe('05.10.2013 14:45');
		
		dateTime.timeZone = 'Europe/Kiev';
		expect(dateTime.toDisplay('2013-10-05 14:45')).toBe('05.10.2013 17:45');
	});	
	
});
