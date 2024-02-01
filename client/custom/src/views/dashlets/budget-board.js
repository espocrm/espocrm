define('custom:views/dashlets/budget-board', ['views/dashlets/abstract/base'],  function (Dep) {
    return Dep.extend({
        name: 'Budget Board',
        template: 'custom:dashlets/budget-board',

        setup: function() {
            this.today = new Date().toISOString().split('T')[0];
            this.value = 0;
            this.count = 0;
            this.entityName = this.getOption('entityName');
            this.teamsIds = this.getOption('teams');
            if (!this.teamsIds) {
                this.teamsIds = this.getUser().get('teamsIds');
            }

            this.wait(
                this.fetchIncome(this.today, this.teamsIds)
            );
        },

        fetchIncome: async function(date, teamsIds) {
            try {
                const teamsParam = 'teams=' + teamsIds.join('&');
                let income = await fetch(`api/v1/Budget/detail/${date}/${teamsParam}`);
                income = await income.json();
                
                const enitityIncome = income.profitDetails.find(detail => detail.name == this.entityName);
                this.value = enitityIncome.value;
                this.count = enitityIncome.count;
                
                return income;
            } catch (error) {
                console.error(error);
            }
        },

        data: function() {
            return { 
                value: this.value.toLocaleString('en'),
                count: this.count
            }
        }
    })
});