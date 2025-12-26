define(['views/modal'], function (ModalView) {

    return ModalView.extend({
        template: 'custom:contact/modals/leasings-details',
        header: 'Leasing Details',
        
        getRenderData: function () {
            return {};
        }
    });
});
