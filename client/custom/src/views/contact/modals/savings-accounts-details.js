define(['views/modal'], function (ModalView) {

    return ModalView.extend({
        template: 'custom:contact/modals/savings-accounts-details',
        header: 'Savings Accounts Details',
        
        getRenderData: function () {
            return {};
        }
    });
});
