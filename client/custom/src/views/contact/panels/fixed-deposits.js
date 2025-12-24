define(['views/record/panels/side'], function (SidePanelView) {

    return SidePanelView.extend({

        template: 'custom:contact/panels/fixed-deposits',

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
        }
    });
});
