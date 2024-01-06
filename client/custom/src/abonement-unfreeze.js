define('custom:abonement-unfreeze', ['action-handler'], function (Dep) {

    return Dep.extend({

        actionUnfreeze: function (data, e) {
            this.view.model.set("isFreezed", false);
            this.view.model.save()
                .then(abon => {
                    this.view.hideHeaderActionItem('unfreeze');
                    this.view.showHeaderActionItem('freeze');
                })
                .catch(error => this.handleError(error));
        },

        initUnfreezing: function () {
            this.view.hideHeaderActionItem('unfreeze');//hide by default to prevent button apear-desapear affect
            Espo.Ajax
                .getRequest('Abonement/' + this.view.model.id)
                .then(abon => this.controlButtonVisibility(abon.isFreezed))
                .catch(error => this.handleError(error));
        },

        controlButtonVisibility: function (isShown) {
            if (isShown) {
                this.view.showHeaderActionItem('unfreeze');
                return;
            }
            this.view.hideHeaderActionItem('unfreeze');
        }
    });
 });