define('custom:views/dashlets/budget-board', ['views/dashlets/abstract/base'],  function (Dep) {
    return Dep.extend({
        name: 'Budget Board',
        template: 'custom:dashlets/budget-board',

        setup: function() {
            this.today = new Date().toISOString().split('T')[0];
            this.records = { list: [], total: 0 };
            this.totalSum = 0;
            
            this.getCollectionFactory().create(this.getOption('object'))
                .then(collection => {
                    collection.maxSize = 10000;
                    collection.where = this.getWhere();
                    return collection.fetch();
                })
                .then(records => {
                    this.records = records;
                    this.totalSum = this.sum(records.list);
                    this.reRender();
                })
                .catch((error) => {
                    console.error(error);
                });
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

        sum: function(recordsList) {
            let totalSum = 0;
            recordsList.forEach(element => {
                totalSum += element[this.getOption('priceFieldName')];
            });
            return totalSum;
        },

        afterRender: function () {
        },

        data: function() {
            return { 
                total: this.records.total,
                totalSum: this.totalSum.toLocaleString('en')
            }
        }
    })
});