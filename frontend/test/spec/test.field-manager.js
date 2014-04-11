var Espo = Espo || {};

describe("FieldManager", function () {
	var fieldManager;
	
	var defs = {
				'varchar': {
					'params': [
						{
							'name': 'required',
							'type': 'bool',
							'default': false
						},
						{
							'name': 'maxLength',
							'type': 'int'
						}
					]
				},
				'int': {
					'params': [
						{
							'name': 'required',
							'type': 'bool',
							'default': false
						},
						{
							'name': 'min',
							'type': 'int'
						},
						{
							'name': 'max',
							'type': 'int'
						}
					]
				},
				'float': {
					'params': [
						{
							'name': 'required',
							'type': 'bool',
							'default': false
						},
						{
							'name': 'min',
							'type': 'float'
						},
						{
							'name': 'max',
							'type': 'float'
						}
					]
				},
				'enum': {
					'params': [
						{
							'name': 'required',
							'type': 'bool',
							'default': false
						},
						{
							'name': 'options',
							'type': 'array'
						}
					]
				},
				'bool': {
					'params': []
				},
				'date': {
					'params': [
						{
							'name': 'required',
							'type': 'bool',
							'default': false
						}
					]
				},
				'datetime': {
					'params': [
						{
							'name': 'required',
							'type': 'bool',
							'default': false
						}
					]
				},
				'link': {
					'params': [
						{
							'name': 'required',
							'type': 'bool',
							'default': false
						}
					],
					'actualFields': ['id']
				},
				'linkParent': {
					'params': [
						{
							'name': 'required',
							'type': 'bool',
							'default': false
						}
					],
					'actualFields': ['id', 'type']
				},
				'linkMultiple': {
					'params': [
						{
							'name': 'required',
							'type': 'bool',
							'default': false
						}
					],
					'actualFields': ['ids']
				},
				'personName': {
					'actualFields': ['salutation', 'first', 'last'],
					'fields': {
						'salutation': {
							'type': 'varchar'
						},
						'first': {
							'type': 'enum'
						},
						'last': {
							'type': 'varchar'
						}
					},
					'naming': 'prefix',
					'mergable': false,
				},
				'address': {
					'actualFields': ['street', 'city', 'state', 'country', 'postalCode'],
					'fields': {
						'street': {
							'type': 'varchar'
						},
						'city': {
							'type': 'varchar'
						},
						'state': {
							'type': 'varchar'
						},
						'country': {
							'type': 'varchar'
						},
						'postalCode': {
							'type': 'varchar'
						},
					},
					'mergable': false,
				},
	};
	
	
	
	beforeEach(function () {	
		fieldManager = new Espo.FieldManager(defs);
	});	
	
	it ('#isMergable should work correctly', function () {
		expect(fieldManager.isMergable('address')).toBe(false);
		expect(fieldManager.isMergable('link')).toBe(true);	
	});
	
	it ('#getActualAttributes should work correctly', function () {
		var fields = fieldManager.getActualAttributes('address', 'billingAddress');
		expect(fields[0]).toBe('billingAddressStreet');
		
		var fields = fieldManager.getActualAttributes('personName', 'name');
		expect(fields[2]).toBe('lastName');
		
		var fields = fieldManager.getActualAttributes('link', 'account');
		expect(fields[0]).toBe('accountId');
		
		var fields = fieldManager.getActualAttributes('varchar', 'name');
		expect(fields[0]).toBe('name');		
	});
	


});
