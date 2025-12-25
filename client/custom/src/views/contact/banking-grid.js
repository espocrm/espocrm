define('custom:views/contact/banking-grid', ['views/record/panels/bottom'], function (Dep) {
    return Dep.extend({
        template: 'custom:contact/banking-grid',

        setup: function () {
            // 1. Hardcoded Banking Data
            this.bankingData = {
                savings: [{ number: '963852741245', status: 'Open', balance: '1,554' }],
                current: [{ number: '123456789', status: 'Active', balance: '5,000' }],
                debitCards: [{ number: '456789******1234', status: 'Active' }],
                creditCards: [{ number: '411111******1111', status: 'Active' }],
                loans: [{ number: 'LN987654321', amount: '50,000' }],
                digital: [{ name: 'Mobile App', status: 'Registered' }]
            };

            // 2. Fetch REAL Cases Dynamically
            this.casesList = [];
            
            this.ajaxGet('Case', {
                where: { contactId: this.model.id },
                maxSize: 5,
                sortBy: 'createdAt',
                asc: false
            })
            .then(function (response) {
                this.casesList = response.list || [];
                this.reRender();
            }.bind(this));
        },

        data: function () {
            return {
                banking: this.bankingData,
                cases: this.casesList,
                hasCases: this.casesList.length > 0
            };
        }
    });
});