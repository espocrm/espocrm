define('custom:generate-schedule', ['action-handler'], function (Dep) {

    return Dep.extend({

        actionGenerate: function (data, e) {
            if (!this.view.collection.models.length) {
                console.log(this.view.collection.models.length);
                Espo.Ui.error('Немає шаблонів для створення', 2000);
                return
            }
            
            this.view.createView('dialog', 'views/modal', {
                templateContent: `<div>
                    <label for="mondayDate">Оберіть тиждень</label><br>
                    <input class="btn btn-default cp" id="mondayDate" type="date" value="${this.view.mondayOfWeek}"/>
                </div>`,
                headerText: 'Створити заняття',
                backdrop: true,
                message: '',
                buttonList: [
                    {
                        name: 'generateTrainings',
                        label: 'Створити',
                        style: 'success',
                        onClick: (view) => {
                            if (this.isLoading) return;
                            this.showLoading(true);

                            const templatesIds = this.view.collection.models.map(m => m.id);
                            if (templatesIds.length < 1) return;

                            this.view.getCollectionFactory().create('TrainingTemplate')
                                .then(collection => {
                                    collection.maxSize = 10000;
                                    collection.where = [{
                                        "type": "in",
                                        "attribute": "id",
                                        "value": templatesIds
                                    }];
                                    return collection.fetch();
                                })
                                .then(templates => {
                                    this.createTrainingsFromTemplates(templates.list);
                                })
                                .catch(error => this.handleError(error))
                        },
                    },
                    {
                        name: 'close',
                        label: 'Скаувати',
                    }
                ],
            }, view => {
                view.render()
                    .then(view => {
                        const dateElement = view.$el.find(`#mondayDate`)[0];
                        dateElement.addEventListener('change', e => {
                            const mondayOfWeek = this.getMondayOfPickedWeek(e.target.value);
                            this.view.mondayOfWeek = mondayOfWeek;
                            e.target.value = mondayOfWeek;
                        });
                    });
            });
            
        },

        initGenerate: function () {
            isLoading = false;
            this.view.mondayOfWeek = this.getMondayOfWeek(new Date(), true);
        },

        getMondayOfPickedWeek: function(dateValue) {
            const [ year, month, day ] = dateValue.split('-');
            const pickedDate = new Date(year, month - 1, day, 0, 0, 0);//month-1, bc start from 0,
            return this.getMondayOfWeek(pickedDate, false);
        },

        getMondayOfWeek: function(date, isForNextWeek) {
            const fromMonday = [6, 0, 1, 2, 3, 4, 5];//start week from monday
            const weekShift = isForNextWeek ? 7 : 0;

            const monday = date.setDate(date.getDate() + weekShift - fromMonday[date.getDay()]);
            const inKievTimezone = new Date(monday).toLocaleString('uk-Ua', { timeZone: 'Europe/Kiev' });
            const formatedKievTimezone = inKievTimezone.split(',')[0].split('.').reverse().join('-');
            return formatedKievTimezone;
        },

        createTrainingsFromTemplates: async function(allTemplates) {
            let weekDayDate = this.view.mondayOfWeek;
            const weekDayNames = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"];
            
            let createTrainingRequests = [];
            weekDayNames.forEach(weekDayName => {
                const forWeekRequests = this.createTrainingsForWeekDay(weekDayName, weekDayDate, allTemplates);
                createTrainingRequests.push(...forWeekRequests);
                weekDayDate = this.getNextWeekDay(weekDayDate);
            });

            Promise.all(createTrainingRequests)
                .then(res => {
                    Espo.Ui.success('Створено', 2000);
                    this.view.clearView('dialog');
                })
                .catch(error => this.handleError(error))
                .finally(() => this.showLoading(false) );
        },

        createTrainingsForWeekDay: function(weekDayName, weekDayDate, allTemplates) {
            const teplatesForWeekDay = allTemplates.filter(template => template.weekDay === weekDayName);
            const trainings = teplatesForWeekDay.map(template => this.templateToTraining(template, weekDayDate)); 
            
            let createTrainingReqWeek = trainings.map(training => this.createTraining(training));
            return createTrainingReqWeek;
        },

        templateToTraining: function(trainingTemplate, weekDayDate) {
            return {
                hallId: trainingTemplate.hallId,
                groupId: trainingTemplate.groupId,
                Status: 'Planned',
                dateStart: this.formateDatetime(weekDayDate, trainingTemplate.startTime),
                dateEnd: this.formateDatetime(weekDayDate, trainingTemplate.endTime),
                assignedUserId: trainingTemplate.trainerId,
                teamsIds: trainingTemplate.assignedTeams
            };
        },

        getNextWeekDay: function(date) {
            const weekDay = new Date(date);
            const nextDay = weekDay.setDate(weekDay.getDate() + 1);
            const inKievTimezone = new Date(nextDay).toLocaleString('uk-Ua', { timeZone: 'Europe/Kiev' });
            return inKievTimezone.split(',')[0].split('.').reverse().join('-');  
        },

        formateDatetime: function(date, time) {
            const isoDateTime = new Date(date + ' ' + time).toISOString();//make shift +0, bc Espo add it 
            let formatedAfterLocaleReset = isoDateTime.split('T')[0];
            formatedAfterLocaleReset += ' ' + isoDateTime.split('T')[1].split('.')[0];
            return formatedAfterLocaleReset;
        },

        createTraining: function(training) {
            return fetch('/api/v1/Training', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(training)
            })
                .catch(error => {
                    console.error(error);
                });
        },

        showLoading: function(isLoading) {
            if (isLoading) {
                this.isLoading = true;
                Espo.Ui.notify('<span class="fas fa-spinner fa-spin">', 'warning', 20000, true);
            } else {
                this.isLoading = false;
            }
        },

        handleError: function(error) {
            Espo.Ui.notify('Помилка', 'error', 2000);
            console.error(error);
        }
    });
 });