define('custom:views/contact/side-banking-tiles', ['views/record/panels/side'], function (Dep) {
    return Dep.extend({
        // We link to our custom template
        template: 'custom:contact/side-banking-tiles',

        setup: function () {
            // Hardcoded Data for the Side Panel
            this.sideData = {
                goldLoans: [
                    { id: 'GL001', amount: '150,000', rate: '22%' }
                ],
                fds: [ // Fixed Deposits
                    { id: 'FD-8899', amount: '500,000', maturity: '2025-12-01' },
                    { id: 'FD-1122', amount: '100,000', maturity: '2024-08-15' }
                ],
                savings: [
                    { id: 'SAV-001', balance: '25,400' }
                ]
            };
        },

        data: function () {
            return {
                banking: this.sideData
            };
        }
    });
});