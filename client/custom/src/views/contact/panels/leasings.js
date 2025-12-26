define(['views/record/panels/side'], function (SidePanelView) {

    return SidePanelView.extend({

        template: 'custom:contact/panels/leasings',

        events: {
            'click .table': 'actionShowDetails'
        },

        setup: function () {
            // No code needed here.
        },

        load: function (callback) {
            if (callback) {
                callback();
            }
        },

        getRenderData: function () {
            return {}; 
        },

        actionShowDetails: function () {
            this.createView('detailsModal', 'custom:views/contact/modals/leasings-details', {}, function (view) {
                view.render();
            });
        }
    });
});
