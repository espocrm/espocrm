define(['views/record/panels/side'], function (SidePanelView) {

    return SidePanelView.extend({

        template: 'custom:contact/panels/banking-loans',

        events: {
            'click .table': 'actionShowDetails'
        },

        // 1. We SKIP parent setup to avoid looking for non-existent relationships.
        setup: function () {
            // No code needed here.
        },

        // 2. We override load to tell the system "We are done loading immediately."
        load: function (callback) {
            if (callback) {
                callback();
            }
        },

        // 3. We don't need to pass data, since it's now in the template.
        getRenderData: function () {
            return {}; 
        },

        actionShowDetails: function () {
            this.createView('detailsModal', 'custom:views/contact/modals/banking-loans-details', {}, function (view) {
                view.render();
            });
        }
    });
});