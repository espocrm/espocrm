define('custom:abonement-freeze', ['action-handler'], function (Dep) {

    return Dep.extend({

        actionFreeze: function (data, e) {
            if (this.isPending(this.view.model.get("startDate"))) {
                Espo.Ui.notify('Очікує дати початку', 'error', 2000);
                return;
            }
            
            this.view.model.set("isFreezed", true);
            this.view.model.save()
                .then(abon => {
                    this.view.hideHeaderActionItem('freeze');
                    this.view.showHeaderActionItem('unfreeze');
                })
                .catch(error => this.handleError(error));
        },

        initFreezing: function () {
            this.view.hideHeaderActionItem('freeze');//hide by default to prevent button apear-desapear affect
            Espo.Ajax
                .getRequest('Abonement/' + this.view.model.id)
                .then(abon => this.controlButtonVisibility(this.isFreezeButtonShown(abon)))
                .catch(error => this.handleError(error));
        },

        controlButtonVisibility: function (isShown) {
            if (isShown) {
                this.view.showHeaderActionItem('freeze');
                return;
            }
            this.view.hideHeaderActionItem('freeze');
        },

        isFreezeButtonShown: function(abon) {
            return abon.isActive && 
                    this.isNotLastDay(abon.endDate);
        },

        isNotLastDay: function(endDate) {
            const daysBeforeEnd = this.daysBetweenToday(endDate);
            return daysBeforeEnd > 0 ? true : false;
        },

        isPending: function(startDate) {
            const daysBeforeStart = this.daysBetweenToday(startDate);
            return daysBeforeStart <= 0 ? false : true;
        },

        daysBetweenToday(date) {
            const today = new Date().toLocaleDateString().split('.').reverse().join('-');
            const freezeMSLeft = new Date(date) - new Date(today);
            const freezeDaysLeft = freezeMSLeft / (1000 * 60 * 60 * 24);

            return freezeDaysLeft;
        },

        handleError: function(error) {
            Espo.Ui.notify('Помилка при замороженні абонементу', 'error', 2000);
            console.log(error);
        }
    });
 });