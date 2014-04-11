var Espo = Espo || {};

describe("Acl", function () {		
	var acl;
	
	beforeEach(function () {		
		acl = new Espo.Acl();	
	});	
	
	it("should check an access properly", function () {	
		acl.set({
			lead: {
				read: 'team',
				edit: 'own',
				delete: 'no',
			},
			contact: {
				read: true,
				edit: false,
				delete: false,
			},
			opportunity: false,
			meeting: true,
		});
		
		expect(acl.check('lead', 'read')).toBe(true);
		
		expect(acl.check('lead', 'read', false, false)).toBe(false);
		expect(acl.check('lead', 'read', false, true)).toBe(true);		
		expect(acl.check('lead', 'read', true)).toBe(true);		
		
		expect(acl.check('lead', 'edit')).toBe(true);
		expect(acl.check('lead', 'edit', false, true)).toBe(false);
		expect(acl.check('lead', 'edit', true, false)).toBe(true);
		
		expect(acl.check('lead', 'delete')).toBe(false);
		
		expect(acl.check('contact', 'read')).toBe(true);
		expect(acl.check('contact', 'edit')).toBe(false);
		
		expect(acl.check('lead', 'convert')).toBe(false);
		expect(acl.check('account', 'edit')).toBe(true);
		
		expect(acl.check('opportunity', 'edit')).toBe(false);
		expect(acl.check('meeting', 'edit')).toBe(true);
	});	
	
});
