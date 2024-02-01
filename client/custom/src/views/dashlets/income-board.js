define('custom:views/dashlets/income-board', ['views/dashlets/abstract/base'],  function (Dep) {
    return Dep.extend({
        name: 'Income Board',
        template: 'custom:dashlets/income-board',

        setup: function() {
            this.today = new Date().toISOString().split('T')[0];
            this.teamsIds = this.getOption("teams");
            console.log(this.teamsIds);
            this.income = 0;
            this.profit = 0;
            this.expenses = 0;

            this.wait(
                this.fetchButget(this.today, this.today, this.teamsIds)
            );
        },

        fetchButget: async function(dateFrom, dateTo, teamsIds) {
            try {
                const teamsParam = 'teams=' + teamsIds.join('&');
                let budget = await fetch(`api/v1/Budget/income/${dateFrom}/${dateTo}/${teamsParam}`);
                budget = await budget.json();
                console.log(budget);
                this.income = budget.total.income;
                this.profit = budget.total.profit;
                this.expenses = budget.total.expenses;
                
                return budget;
            } catch (error) {
                console.error(error);
            }
        },

        afterRender: function () {
        },

        data: function() {
            return {
                income: this.income.toLocaleString('en'),
                profit: this.profit.toLocaleString('en'),
                expenses: this.expenses.toLocaleString('en'),
                isProfit: this.profit - this.expenses > 0 ? true : false
            }
        }
    })
});