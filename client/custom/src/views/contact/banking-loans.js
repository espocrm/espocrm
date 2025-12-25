define(['views/record/panels/side'], function (SidePanelView) {

    return SidePanelView.extend({

        // Make sure this path matches your file structure exactly
        template: 'custom:contact/panels/banking-loans',

        setup: function () {
            // Run standard setup
            SidePanelView.prototype.setup.call(this);

            // Hardcoded data
            this.data.loanList = [
                { type: 'Home Loan', amount: '$250,000', status: 'Active' },
                { type: 'Car Loan', amount: '$15,000', status: 'Paid' }
            ];
        }
    });
});