define(['views/modal'], function (ModalView) {

    return ModalView.extend({
        template: 'custom:contact/modals/gold-loans-details',
        header: 'Gold Loans Details',
        
        getRenderData: function () {
            return {};
        }
    });
});
