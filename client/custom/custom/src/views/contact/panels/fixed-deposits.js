define(['views/record/panels/side'], function (SidePanelView) {

    return SidePanelView.extend({

        template: 'custom:contact/panels/fixed-deposits',

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
            this.createView('detailsModal', 'custom:views/contact/modals/fixed-deposits-details', {}, function (view) {
                view.render();
            });
        }
    });
});
