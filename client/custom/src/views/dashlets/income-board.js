define('custom:views/dashlets/income-board', ['views/dashlets/abstract/base'],  function (Dep) {
    return Dep.extend({
        name: 'Income Board',
        template: 'custom:dashlets/income-board',

        setup: function() {
            this.today = new Date().toISOString().split('T')[0];
            this.abonements = { list: [], total: 0, totalSum: 0 };
            this.indivs = { list: [], total: 0, totalSum: 0 };
            this.rents = { list: [], total: 0, totalSum: 0 };
            this.rentplans = { list: [], total: 0, totalSum: 0 };
            this.expenses = { list: [], total: 0, totalSum: 0 };
            this.totalSum = 0;
            this.income = 0;
            this.profit = 0;
            this.spending = 0;

            
            Promise.all([
                this.fetchRecords('Abonement', this.abonements, 'price'),
                this.fetchRecords('Indiv', this.indivs, 'defaultPrice'),
                this.fetchRecords('Rent', this.rents, 'customPrice'),
                this.fetchRecords('RentPlan', this.rentplans, 'price'),
                this.fetchRecords('Expenses', this.expenses, 'cost'),
            ])
            .then(summaries => {
                console.log(summaries);
                this.profit = summaries[0] + summaries[1] + summaries[2] + summaries[3];
                this.spending = summaries[4];
                this.reRender();
            })
            .catch(error => {
                console.error(error);
            });
            
            
        },

        fetchRecords: async function(entityType, recordList, priceFieldName) {
            try {
                const collection = await this.getCollectionFactory().create(entityType)
                
                collection.maxSize = 10000;
                collection.where = this.getWhere();
                
                const records = await collection.fetch();
                
                recordList = records;
                recordList.totalSum = this.sum(recordList.list, priceFieldName);
                
                return recordList.totalSum;
            } catch (error) {
                console.error(error);
            }
        },

        getWhere: function() {
            const where =  [{
                "type": "greaterThanOrEquals",
                "attribute": "createdAt",
                "value": this.today + " 00:00:00"
            }, {
                "type": "lessThanOrEquals",
                "attribute": "createdAt",
                "value": this.today + " 23:59:59"
            }];

            const optionalFilters = this.getOptionalFilters();
            if (optionalFilters.length) {
                where.push(...optionalFilters);
            }
            
            return where;
        },

        getOptionalFilters: function() {
            let parsed = {};
            try {
                parsed = JSON.parse(this.getOption("filters"));
            } catch (e) {
                return [];
            }
            return parsed.filters;
        },

        sum: function(recordsList, priceFieldName) {
            let totalSum = 0;
            recordsList.forEach(element => {
                totalSum += element[priceFieldName];
            });
            return totalSum;
        },

        afterRender: function () {
        },

        data: function() {
            return {
                income: (this.profit - this.spending).toLocaleString('en'),
                profit: this.profit.toLocaleString('en'),
                spending: this.spending.toLocaleString('en'),
                isProfit: this.profit - this.spending > 0 ? true : false
            }
        }
    })
});