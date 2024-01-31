define('custom:views/calculations/budget', ['view'], function (View) {
    return View.extend({
        template: 'custom:calculations/budget',

        setup: function () {
            this.isSuperadmin = this.getUser().get('type') === 'admin' ? true : false;
            this.filterValue = 'today';
            this.dateFrom = new Date().toLocaleDateString().split('.').reverse().join('-');
            this.dateTo = this.dateFrom;
            this.teams = { total: 0, list: [] };
            this.choosedTeams = this.getUser().get('teamsIds');
            this.sum = 0;
            this.income = { 
                list: [],
                total: { 
                    expenses: 0, 
                    profit: 0,
                    income: 0
                }
            }
            
            this.wait(
                Promise.all([
                    this.fetchTeams(),
                    this.fetchButget(this.dateFrom, this.dateFrom, this.choosedTeams)
                ])
            );
            this.initHandlers();
        },

        initHandlers: function() {
            this.addHandler('click', '.btn-date', 'handleDate');
            this.addHandler('click', '.btn-team', 'handleTeam');

            this.addHandler('change', '#dateBetween1', 'handleChangeDateFrom');
            this.addHandler('change', '#dateBetween2', 'handleChangeDateTo');
            
            this.addHandler('click', '#findBetweenDates', 'findBetweenDates');
            this.addHandler('click', '.expander', 'handleAction');
        },

        handleDate: function(e) {
            const action = e.target.dataset.action;
            const date = e.target.dataset.date;
            
            this[action](e);
        },

        filterToday: function(e) {
            this.filterValue = e.target.value;
            this.dateFrom = new Date().toLocaleDateString().split('.').reverse().join('-');
            this.fetchButget(this.dateFrom, this.dateFrom, this.choosedTeams)
                .then(() => this.reRender());
        },

        filterWeek: function(e) {
            this.filterValue = e.target.value;
            this.setWeekRange();
            this.fetchButget(this.dateFrom, this.dateTo, this.choosedTeams)
                .then(() => this.reRender());
        },

        setWeekRange: function() {
            const today = new Date();
            const firstDateOfMonth = new Date(
                today.setDate(today.getDate() - today.getDay() + 1)//+1 from monday
            );
            const lastDateOfMonth = new Date(
                today.setDate(firstDateOfMonth.getDate() + 6)
            );
            
            this.dateFrom = firstDateOfMonth.toLocaleDateString().split('.').reverse().join('-');
            this.dateTo = lastDateOfMonth.toLocaleDateString().split('.').reverse().join('-');
        },

        filterMonth: function(e) {
            this.filterValue = e.target.value;
            this.setMonthRange();
            this.fetchButget(this.dateFrom, this.dateTo, this.choosedTeams)
                .then(() => this.reRender());
        },

        setMonthRange:function() {
            const date = new Date();
            const firstDateOfMonth = new Date(date.getFullYear(), date.getMonth(), 1);
            const lastDateOfMonth = new Date(date.getFullYear(), date.getMonth() + 1, 0);

            this.dateFrom = firstDateOfMonth.toLocaleDateString().split('.').reverse().join('-');
            this.dateTo = lastDateOfMonth.toLocaleDateString().split('.').reverse().join('-');
        },

        filterBetweenDates: function(e) {
            this.filterValue = e.target.value;
            this.reRender();
        },

        handleChangeDateFrom: function(e) {
            this.dateFrom = e.target.value;
        },

        handleChangeDateTo: function(e) {
            this.dateTo = e.target.value;
        },

        findBetweenDates: function(e) {
            this.fetchButget(this.dateFrom, this.dateTo, this.choosedTeams)
                .then(() => this.reRender());
        },

        handleAction: function(e) {
            const action = e.target.dataset.action;
            const date = e.target.dataset.date;

            this[action](date);
        },

        handleTeam: function(e) {
            const teamId = e.target.value;
            
            const choosedTeamIndex = this.choosedTeams.findIndex(team => team == teamId);
            if (choosedTeamIndex === -1) {
                this.choosedTeams.push(teamId);
            } else {
                this.choosedTeams.splice(choosedTeamIndex, 1);
            }
            this.reRender();
        },

        showDetails: function(date) {
            this.fetchDetails(date, this.choosedTeams)
                .then(() => this.reRender());
        },

        hideDetails: function(date) {
            const income = this.income.list.find(income => income.date == date);
            income.isExpanded = false;
            this.reRender();
        },

        fetchTeams: async function() {
            try {
                let teamsCollection = await this.getCollectionFactory().create('Team');
                this.teams = await teamsCollection.fetch();

                return this.teams;
            } catch (error) {
                console.error(error);
            }
        },

        fetchButget: async function(dateFrom, dateTo, teamsIds) {
            try {
                const teamsParam = 'teams=' + teamsIds.join('&');
                let income = await fetch(`api/v1/Budget/income/${dateFrom}/${dateTo}/${teamsParam}`);
                income = await income.json();
                this.income = income;
                
                return income;
            } catch (error) {
                console.error(error);
            }
        },

        fetchDetails: async function(date, teamsIds) {
            try {
                const teamsParam = 'teams=' + teamsIds.join('&');
                let details = await fetch(`api/v1/Budget/detail/${date}/${teamsParam}`);
                details = await details.json();
                this.details = details;

                const income = this.income.list.find(income => income.date == date);
                income.profitDetailsTable = this.createDetailsTable(details.profitDetails);
                income.expensesDetailsTable = this.createDetailsTable(details.expensesDetails);
                income.isExpanded = true;

                return details;
            } catch (error) {
                console.error(error);
            }
        },

        createDetailsTable: function(detailsList) {
            let detailsTable = `<table class="table table-details" style="border-radius: 5px;">`;
            detailsList.forEach(details => {
                detailsTable += 
                `<tr class="list-row">
                    <td class="cell">${ details.value.toLocaleString('en') }</td>
                    <td class="cell">${ details.name }</td>
                </tr>`
            });
            detailsTable += `</table>`;

            return detailsTable;
        },
        
        afterRender: function () {
            //hightlight button with blue
            this.$el.find(`button[value=${this.filterValue}`)[0].classList.add('btn-primary');

            this.choosedTeams.forEach(teamId => {
                this.$el.find(`button[value="${teamId}"`)[0].classList.add('btn-primary');
            });
        },

        data: function () {
            return {
                isSuperadmin: this.isSuperadmin,
                teams: this.teams.list,
                filterValue: this.filterValue,
                dateFrom: this.dateFrom,
                dateTo: this.dateTo,

                profitTotalSum: this.income.total.profit.toLocaleString('en'),
                expensesTotalSum: this.income.total.expenses.toLocaleString('en'),
                incomeTotalSum: this.income.total.income.toLocaleString('en'),

                incomeList: this.formatePriceFields(this.income.list)
            };
        },

        formatePriceFields: function(budgetList) {
            return budgetList.map(budget => {
                return {
                    date: budget.date,
                    isExpanded: budget.isExpanded,
                    profitDetailsTable: budget.profitDetailsTable,
                    expensesDetailsTable: budget.expensesDetailsTable,
                    isIncome: (budget.profit - budget.expenses) >= 0 ? true : false,
                    profit: budget.profit.toLocaleString('en'),
                    expenses: budget.expenses.toLocaleString('en'),
                    income: budget.income.toLocaleString('en')
                }   
            });
        }
    });
});