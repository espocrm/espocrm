define(['views/modal'], function (ModalView) {

    return ModalView.extend({
        template: 'custom:contact/modals/banking-loans-details',
        header: 'Banking Loans Details',
        
        getRenderData: function () {
            return {};
        }
    });
});
