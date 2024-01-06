define('custom:views/attendance-sheet/trainings-panel', ['view'],  function (Dep) {
    return Dep.extend({
        name: 'trainings-panel',
        template: 'custom:attendance-sheet/trainings-panel',

        setup: function() {
            this.activities = { list: [], total: 0 };
            this.halls = [];
            this.selectedActivityId = null;
            this.activityHall = 'all';
            this.activityDate = new Date().toLocaleDateString().split('.').reverse().join('-');
            this.today = this.activityDate;

            this.wait(
                Promise.all([
                    this.getCollectionFactory().create('Training')
                        .then(collection => {
                            collection.maxSize = 10000;
                            collection.where = [{
                                "type": "equals",
                                "attribute": "startDateOnly",
                                "value": this.activityDate 
                            }];
                            return collection.fetch();
                        })
                        .then(trainings => this.setActivities(trainings))
                        .catch((error) => {
                            console.error(error);
                        }),
                    this.getCollectionFactory().create('Hall')
                        .then(collection => {
                            collection.maxSize = 10000;
                            return collection.fetch();
                        })
                        .then(halls => {
                            this.halls = halls;
                        })
                        .catch((error) => {
                            console.error(error);
                        })
                    ])
            );

            this.initHandlers();
        },

        initHandlers: function() {
            this.addHandler('click', '.activity', 'chooseActivitie');
            this.addHandler('change', '#date', 'handleDate');
            this.addHandler('change', '#hall', 'handleHall');
            this.addHandler('click', `[data-action="addTraining"]`, 'handleAction');
            this.addHandler('click', `[data-action="editTraining"]`, 'handleAction');
        },

        afterRender: function () {
            const hallElement = this.$el.find(`option[value=${this.activityHall}]`)[0];
            if (hallElement) {
                hallElement.selected = true;
            }
            if (this.selectedActivityId) {
                const activityTableRow = this.$el.find(`tr[data-training-id=${this.selectedActivityId}]`)[0];
                if (activityTableRow) {
                    activityTableRow.classList.add('text-warning');
                }
            }
        },

        handleAction: function(e) {
            this[e.target.dataset.action](e);
        },

        addTraining: function(e) {
            this.showModalLoading(true);

            this.getModelFactory().create('Training')
                .then(model => {
                    model.defs.fields.dateStart.default = this.activityDate + " 13:00:00";

                    let options = {
                        scope: 'Training',
                        model: model
                    };

                    const updateTrainingsList = function(trainingModel) { 
                        const newTraining = {
                            id: trainingModel.get('id'),
                            groupId: trainingModel.get('groupId'),
                            groupName: trainingModel.get('groupName'),
                            name: trainingModel.get('name'),
                            assignedUserName: trainingModel.get('assignedUserName'),
                            timeDuration: this.formateTimeDuration(trainingModel.get('dateStart'))
                        };
                        this.activities.list.push(newTraining);
                        this.activities.total++;
                        this.reRender();
                    }
                    
                    this.createView('TrainingCreate', 'views/modals/edit', options, view => {
                        view.render();
                        this.showModalLoading(false);
                        this.listenToOnce(view, 'after:save', updateTrainingsList);
                    });
            })
            .catch(error => this.handleError(error));
        },

        editTraining: function(event) {
            this.showModalLoading(true);
            let options = {
                scope: 'Training',
                id: event.target.dataset.trainingId
            };
            this.createView('trainingEdit', 'views/modals/edit', options, view => {
                view.render();
                this.showModalLoading(false);
                
                this.listenToOnce(view, 'after:save', () => {
                    this.filterActivities();
                });
            });
        },

        handleHall: function(e) {
            this.activityHall = e.target.value;
            this.filterActivities();
        },

        handleDate: function(e) {
            this.activityDate = e.target.value;
            this.filterActivities();
        },

        filterActivities: function() {
            this.getCollectionFactory().create('Training')
                .then(collection => {
                    const conditionDate = {
                        "type": "equals",
                        "attribute": "startDateOnly",
                        "value": this.activityDate
                    };
                    const conditionHall = {
                        "type": "equals",
                        "attribute": "hallId",
                        "value": this.activityHall,
                    };
                    const conditions = [ conditionDate ];
                    if (this.activityHall !== 'all') {
                        conditions.push(conditionHall);
                    }
                    collection.maxSize = 10000;
                    collection.where = conditions;
                    return collection.fetch();
                })
                .then(trainings => {
                    this.setActivities(trainings);
                    this.reRender();
                })
                .catch((error) => {
                    console.error(error);
                });
        },

        setActivities: function(records) {
            this.activities = records;
            this.activities.list.forEach(activity => { 
                activity.timeDuration = this.formateTimeDuration(activity.dateStart)
            });
            this.sortByTimeDuration(this.activities.list);
        },

        chooseActivitie: function(e) {
            if (e.target.dataset.action) return;
            
            const trainingId = e.target.parentElement.dataset.trainingId;
            this.selectedActivityId = trainingId;
            const training = this.activities.list.find(training => training.id === trainingId);
            const groupId = training.groupId;
            const groupName = training.groupName;
            
            this.trigger('activity:changed', { trainingId, groupId, groupName });
            this.reRender();
        },

        formateTimeDuration: function(dateStart) {
            const dateStartLocale = this.convertUTCToLocal(dateStart);
            return this.getTimeOnly(dateStartLocale);
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

        getTimeOnly: function(dateTime) {
            const timeHMS = dateTime.split(" ")[1];
            const timeHM = timeHMS.split(":")[0] + ":" + timeHMS.split(":")[1];
            return timeHM;
        },

        sortByTimeDuration: function(activities) {
            activities.sort((a, b) => {
                if (a.timeDuration > b.timeDuration) return 1;
                if (a.timeDuration < b.timeDuration) return -1;
                return 0;
            });
        },

        showModalLoading: function(isLoading) {
            if (isLoading) {
                Espo.Ui.notify('<span class="fas fa-spinner fa-spin">', 'warning', 20000, true);
            } else {
                Espo.Ui.notify('', 'warning', 1);
            }
        },

        data: function() {
            return {
                halls: this.halls.list,
                activities: this.activities.list,
                activitiesTotal: this.activities.total,
                activityDate: this.activityDate,
                activityHall: this.activityHall
            }
        },
    })
});