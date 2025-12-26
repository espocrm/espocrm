define(['views/modal'], function (ModalView) {

    return ModalView.extend({
        template: 'custom:contact/modals/fixed-deposits-details',
        header: 'Fixed Deposits Details',
        
        getRenderData: function () {
            return {};
        }
    });
});
