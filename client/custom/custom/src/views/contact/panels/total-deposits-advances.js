define(['views/record/panels/bottom'], function (BottomPanelView) {

    return BottomPanelView.extend({
        template: 'custom:contact/panels/total-deposits-advances',
        
        setup: function () {
            // No setup needed for static data
        }
    });
});
