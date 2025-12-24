define(['views/modal'], function (ModalView) {

    return ModalView.extend({
        template: 'custom:contact/modals/factoring-details',
        header: 'Factoring Details',
        
        getRenderData: function () {
            return {};
        }
    });
});
