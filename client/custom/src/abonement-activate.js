define('custom:abonement-activate', ['action-handler'], function (Dep) {

    return Dep.extend({

        actionActivate: function (data, e) {
            if (!this.view.model.get("isPaid")) {
                Espo.Ui.notify('Абонемент не сплачений', 'error', 2000);
                return;
            }
            this.view.model.set("isActivated", true);
            this.view.model.save()
                .then(abon => {
                    this.view.hideHeaderActionItem('activate');
                })
                .catch(error => this.handleError(error));
        },

        initActivation: function () {
            this.view.hideHeaderActionItem('activate');//hide by default to prevent button apear-desapear affect
            Espo.Ajax
                .getRequest('Abonement/' + this.view.model.id)
                .then(abon => this.controlButtonVisibility(abon.isActivated))
                .catch(error => this.handleError(error));
        },

        controlButtonVisibility: function (isActivated) {
            if (isActivated) {
                this.view.hideHeaderActionItem('activate');
                return;
            }
            this.view.showHeaderActionItem('activate');
        },

        handleError: function(error) {
            Espo.Ui.notify('Помилка при активації', 'error', 2000);
            console.log(error);
        }
    });
 });