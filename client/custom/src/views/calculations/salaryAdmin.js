define('custom:views/calculations/salaryAdmin', ['view'], function (View) {
    const SETTINGS_REPLACEMENT_NAME = 'Заміна';

    return View.extend({
        template: 'custom:calculations/salaryAdmin',
        
        setup: function () {
            this.filterValue = 'today';
            this.dateValue1 = new Date().toLocaleDateString().split('.').reverse().join('-');
            this.dateValue2 = this.dateValue1;
            this.categories = [];
            this.userList = [];
            this.trainers = { list: [], total: 0 };
            this.trainings = { list: [], total: 0 };
            this.fines = { list: [], totl: 0 };
            this.marks = { list: [], total: 0 };
            this.totalSalary = 0;
            this.replacementValue = null;

            this.initHandlers();
            this.wait(
                Promise.all([
                    this.fetchCategoryItems()
                        .then(categories => this.categories = categories)
                        .catch(error => console.error(error)),
                    this.fetchCustomSettings()
                        .then(settings => this.replacementValue = Number(settings.list[0].value))
                        .catch(error => console.error(error))
                ])
            );
        },

        initHandlers: function() {
            this.addHandler('click', '#filterToday', 'handleFilterToday');
            this.addHandler('click', '#filterDate', 'handleFilterDate');
            this.addHandler('click', '#filterBetween', 'handleFilterDate');
            this.addHandler('change', '#dateDate', 'handleChangeDate');
            this.addHandler('change', '#dateBetween1', 'handleDateBetween1');
            this.addHandler('change', '#dateBetween2', 'handleDateBetween2');
            this.addHandler('click', '#chooseTrainers', 'handleChooseTrainers');
            this.addHandler('click', '#calculate', 'handleCalculate');
        },

        handleFilterToday: function(e) {
            this.filterValue = e.target.value;
            this.dateValue1 = new Date().toLocaleDateString().split('.').reverse().join('-');
            this.reRender();
        },

        handleFilterDate: function(e) {
            this.filterValue = e.target.value;
            this.reRender();
        },

        handleChangeDate: function(e) {
            this.dateValue1 = e.target.value;
        },

        handleDateBetween1: function(e) {
            this.dateValue1 = e.target.value;
        },

        handleDateBetween2: function(e) {
            this.dateValue2 = e.target.value;
        },

        handleChooseTrainers: function(e) {
            this.createView('dialog', 'crm:views/calendar/modals/shared-options', {
                userList: this.userList,
            }, view => {
                view.render();
                this.listenToOnce(view, 'save', data => {
                    this.userList = data.userList;
                    this.calculate(this.userList);
                });
            });
        },

        handleCalculate: function(e) {
            this.calculate(this.userList);
        },

        calculate: function(userList) {
            this.fetchTrainers(userList)
                    .then(trainers => {
                        trainers.list = userList.map(user => trainers.list.find(trainer => trainer.id === user.id));
                        this.trainers = trainers;
                        return this.fetchTrainings(trainers.list);
                    })
                    .then(trainings => {
                        this.trainings = trainings;
                        return this.fetchMarks(trainings.list);
                    })
                    .then(marks => {
                        this.marks = marks;
                        return this.fetchFines(this.trainers.list);
                    })
                    .then(fines => {
                        this.fines = fines;
                        this.attachMarksToTrainings(this.marks.list, this.trainings.list);
                        this.attachTrainingsToTrainer(this.trainings.list, this.trainers.list);
                        this.attachFinesToTrainers(this.fines.list, this.trainers.list);
                        this.calculateSalary();
                        this.calculateFines();
                        this.calculateTotalSalary();
                        this.createTrainingsTable();
                    })
                    .catch(error => console.error(error))
                    .finally(() => this.reRender());
        },

        fetchTrainers: function(users) {
            if (!users.length) return Promise.resolve({ list: [], total: 0 });

            const userIds = users.map(user => user.id);
            return this.getCollectionFactory().create('User')
                .then(collection => {
                    collection.maxSize = 10000;
                    collection.where = [{
                        "type": "in",
                        "attribute": "id",
                        "value": userIds,
                    }];
                    return collection.fetch();
                })
        },

        fetchTrainings: function(trainers) {
            if (!trainers.length) return { list: [], total: 0 };

            const userIds = trainers.map(user => user.id);
            return this.getCollectionFactory().create('Training')
                .then(collection => {
                    collection.maxSize = 10000;
                    collection.where = this.getTrainingClause(userIds);
                    return collection.fetch();
                })
        },

        fetchMarks: function(trainings) {
            if (!trainings.length) return { list: [], total: 0 }; 

            const trainingIds = trainings.map(training => training.id);
            return this.getCollectionFactory().create('Mark')
                .then(collection => {
                    collection.maxSize = 10000;
                    collection.where = [{
                        "type": "in",
                        "attribute": "trainingId",
                        "value": trainingIds,
                    }];
                    return collection.fetch();
                })
        },

        fetchCategoryItems: function() {
            return this.getCollectionFactory().create('TrainerCategoryItem')
                .then(collection => {
                    collection.maxSize = 1000;
                    return collection.fetch();
                })
        },

        fetchCustomSettings: function() {
            return this.getCollectionFactory().create('CustomSettings')
                .then(collection => {
                    collection.maxSize = 1000;
                    collection.where = [{
                        "type": "equals",
                        "attribute": "name",
                        "value": SETTINGS_REPLACEMENT_NAME,
                    }];
                    return collection.fetch();
                });
        },

        fetchFines: function(trainers) {
            if (!trainers.length) return { list: [], total: 0 };

            const userIds = trainers.map(user => user.id);
            return this.getCollectionFactory().create('Fine')
                .then(collection => {
                    collection.maxSize = 10000;
                    collection.where = [{
                        "type": "in",
                        "attribute": "userId",
                        "value": userIds,
                    }];
                    collection.where.push(this.dateClause("date"));
                    return collection.fetch();
                });
        },

        getTrainingClause: function(userIds) {
            const trainingClause = [];
            trainingClause.push({
                "type": "in",
                "attribute": "assignedUserId",
                "value": userIds,
            });
            trainingClause.push(this.dateClause("startDateOnly"));
            return trainingClause;
        },

        dateClause  : function(dateFieldName) {
            if (this.filterValue === 'today' || this.filterValue === 'date') {
                return {
                    "type": "equals",
                    "attribute": dateFieldName,
                    "value": this.dateValue1
                }
            }
            if (this.filterValue === 'between') {
                return {
                    "type": "between",
                    "attribute": dateFieldName,
                    "value": [this.dateValue1, this.dateValue2],
                }
            }
        },

        attachTrainingsToTrainer: function(trainings, trainers) {
            trainers.forEach(trainer => trainer.trainings = []);
            trainings.forEach(training => {
                trainers.forEach(trainer => {
                    if ((training.assignedUserId === trainer.id) || (training.replacedWho === trainer.id)) {
                        trainer.trainings.push(structuredClone(training));
                    } 
                });
            });
        },

        attachMarksToTrainings: function(marks, trainings) {
            trainings.forEach(training => training.marks = []);
            marks.forEach(mark => {
                trainings.forEach(training => {
                    if (training.id === mark.trainingId) {
                        training.marks.push(mark);
                    } 
                });
            });
        },

        attachFinesToTrainers: function(fines, trainers) {
            trainers.forEach(trainer => trainer.fines = []);
            fines.forEach(fine => {
                trainers.forEach(trainer => {
                    if (fine.userId === trainer.id) {
                        trainer.fines.push(structuredClone(fine));
                    } 
                });
            });
        },

        calculateSalary: function() {
            let trainingsTotalAmount = 0;
            this.trainers.list.forEach(trainer => {
                trainer.trainings.forEach(training => {
                    const categoryItem = this.getCategoryItem(training, trainer.trainerCategoryName);
                    if (training.replacementId) {
                        if (!this.isTrainingOwner(trainer, training)) {
                            training.amount = this.replacementValue;
                            trainingsTotalAmount += this.replacementValue;    
                        } else {
                            if (categoryItem) {
                                training.amount = categoryItem.amount;
                                trainingsTotalAmount += training.amount - this.replacementValue;
                            }    
                        }
                    } else {
                        if (categoryItem) {
                            training.amount = categoryItem.amount;
                            trainingsTotalAmount += training.amount;
                        }
                    }
                });
                trainer.trainingsTotalAmount = trainingsTotalAmount.toLocaleString('en');
                trainingsTotalAmount = 0;
            });
        },

        getCategoryItem: function(training, categoryName) {
            const marksCount = training.marks.length;
            if (!marksCount) return { amount: 0 };

            const categoryItem = this.categories.list.find(cat => 
                cat.trainerCategoryName === categoryName && 
                marksCount >= cat.from  && marksCount <= cat.to
            );

            return categoryItem;
        },

        calculateFines: function() {
            this.trainers.list.forEach(trainer => {
                let finesTotal = 0;
                trainer.fines.forEach(fine => {
                    finesTotal += fine.amount;
                })
                trainer.finesTotalAmount = finesTotal;
                trainer.allTotalAmount = trainer.trainingsTotalAmount - finesTotal; 
            });
        },

        calculateTotalSalary: function() {
            let totalSalary = 0;
            this.trainers.list.forEach(trainer => {
                totalSalary += trainer.allTotalAmount;
            });
            this.totalSalary = totalSalary;
        },

        createTrainingsTable: function() {
            this.trainers.list.forEach(trainer => {
                trainer.trainingsTemplate = '';
                trainer.trainings.forEach(training => {
                    trainer.trainingsTemplate += this.createTrainingTableRow(trainer, training);
                });
                trainer.fines.forEach(fine => {
                    trainer.trainingsTemplate += this.createFineTableRow(fine);
                });
            });
        },

        createTrainingTableRow: function(trainer, training) {
            let trainingRow = '';
            trainingRow += `<tr class="list-row">`;
            trainingRow += this.createTrainingAmountCell(trainer, training);
            trainingRow += `
                <td class="cell col-sm-2">${training.marks.length}</td>
                <td class="cell col-sm-3">${this.convertUTCToLocal(training.dateStart)}</td>
                <td class="cell col-sm-3">${training.name}</td>
            `;
            if (this.isTrainingOwner(trainer, training)) {
                trainingRow += `<td class="cell"><span class="label label-info">${training.replacementName}</span></td>`;
            } else {
                trainingRow += `<td class="cell"></td>`;
            }
            trainingRow += `</tr>`;
            return trainingRow;
        },

        createTrainingAmountCell: function(trainer, training) {
            let amountCell = '';
            if (training.amount == undefined) {
                amountCell += `<td class="cell col-sm-2"><span class="label label-danger">Немає категорії</span></td>`;
            } else {
                if (training.replacementId) {
                    amountCell += this.createAmountCellForReplacement(trainer, training);
                } else {
                    amountCell += `<td class="cell col-sm-2">${training.amount}</td>`;
                }
            }
            return amountCell;
        },

        createAmountCellForReplacement: function(trainer, training) {
            let amountCell = '';
            if (this.isTrainingOwner(trainer, training)) {
                amountCell += `<td class="cell col-sm-2">
                        ${training.amount - this.replacementValue} 
                        <label class="control-label small" style="text-wrap: nowrap">
                            (${training.amount} - ${this.replacementValue})
                        </label>
                    </td>`;
            } else {
                amountCell += `<td class="cell col-sm-2">+${training.amount}</td>`;
            }
            return amountCell;
        },

        isTrainingOwner: function(trainer, training) {
            return trainer.id === training.replacedWho;
        },

        createFineTableRow: function(fine) {
            let fineRow = '';
            fineRow += `<tr class="list-row">`;
            fineRow += `
                <tr class="list-row">
                    <td class="cell">-${fine.amount}</td>
                    <td class="cell">${new Date(fine.date).toLocaleDateString()}</td>
                    <td class="cell">${fine.description}</td>
                    <td class="cell"></td>
                    <td class="cell"></td>
                </tr>
            `;
            return fineRow;
        },

        convertUTCToLocal: function(dateTime) {
            let isoString = dateTime.split(' ').join('T') + '.0000Z';
            return this.formateTime(new Date(isoString).toLocaleString());
        },

        formateTime: function(dateTime) {
            const dateTimeAsArray = dateTime.split(', ');
            const timeInArray = dateTimeAsArray[1].split(':');
            const timeWithoutSeconds = timeInArray[0] + ":" + timeInArray[1];
            return dateTimeAsArray[0] + ' ' + timeWithoutSeconds;
        },

        afterRender: function () {
           //hightlight button with blue
           this.$el.find(`button[value=${this.filterValue}`)[0].classList.add('btn-primary');
        },

        data: function () {
            return {
                filterValue: this.filterValue,
                dateValue1: this.dateValue1,
                dateValue2: this.dateValue2,
                userListTotal: this.trainers.total,
                trainers: this.trainers.list,
                totalSalary: this.totalSalary.toLocaleString('en'),
            };
        },
    });
});