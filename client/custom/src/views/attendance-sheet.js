define('custom:views/attendance-sheet', ['view'],  function (Dep) {
    return Dep.extend({
        name: 'attendance-sheet',
        template: 'custom:attendance-sheet',

        setup: function() {
            this.createView("TrainingsPanel", "custom:views/attendance-sheet/trainings-panel", {
                fullSelector: this.getSelector() + ' .trainings-panel',
                name: "TrainingsPanel"
            }, view => {
                this.listenTo(view, "activity:changed", payload => {
                    const abonementPanel = this.getView("AbonementsPanel");
                    abonementPanel.fetchAbonements(payload.trainingId, payload.groupId, payload.groupName);
                })
            });

            this.createView("AbonementsPanel", "custom:views/attendance-sheet/abonements-panel", {
                fullSelector: this.getSelector() + ' .abonements-panel',
                name: "AbonementsPanel"
            })
        },

        afterRender: function () {
            this.getView("TrainingsPanel").render();
            this.getView("AbonementsPanel").render();
        }
    })
});