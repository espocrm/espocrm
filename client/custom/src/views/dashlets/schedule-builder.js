define('custom:views/dashlets/schedule-builder', ['views/dashlets/abstract/base'],  function (Dep) {
    return Dep.extend({
        name: 'SheduleBuilder',
        template: 'custom:dashlets/schedule-builder',

        setup: function() {
            this.isGenerated = false;
            this.weekTemplates = [];
            this.weekTemplateId = null;
            this.mondayOfWeek = this.getMondayOfWeek(new Date(), true);

            this.wait(
                this.getCollectionFactory().create('WeekTemplate')
                    .then(collection => {
                        collection.maxSize = 10000;
                        return collection.fetch();
                    })
                    .then(records => {
                        this.weekTemplates = records;
                        this.weekTemplateId = records.total ? records.list[0].id : null;
                    })
                    .catch((error) => {
                        console.error(error);
                    })
            );

            this.addHandler('change', '#dateStart', 'handleDate');
            this.addHandler('change', '#weekTemplate', 'handleWeekTemplate');
            this.addHandler('click', '#createTrainings', 'handleCreateTrainings')
        },

        data: function() {
            return {
                weekTemplates: this.weekTemplates.list,
                mondayOfWeek: this.mondayOfWeek,
                isGenerated: this.isGenerated
            }
        },

        handleWeekTemplate: function(e) {
            this.weekTemplateId = e.target.value;
        },

        handleDate: function(e) {
            const [ year, month, day ] = e.target.value.split('-');
            const pickedDate = new Date(year, month - 1, day, 0, 0, 0);//month-1, bc start from 0,
            this.mondayOfWeek = this.getMondayOfWeek(pickedDate, false);
            this.reRender();
        },

        getMondayOfWeek: function(date, isForNextWeek) {
            const fromMonday = [6, 0, 1, 2, 3, 4, 5];//start week from monday
            const weekShift = isForNextWeek ? 7 : 0;

            const monday = date.setDate(date.getDate() + weekShift - fromMonday[date.getDay()]);
            const inKievTimezone = new Date(monday).toLocaleString('uk-Ua', { timeZone: 'Europe/Kiev' });
            const formatedKievTimezone = inKievTimezone.split(',')[0].split('.').reverse().join('-');
            return formatedKievTimezone;
        },

        handleCreateTrainings: function(e) {
            const weekTemplate = this.weekTemplates.list.find(wt => wt.id === this.weekTemplateId);
            if (!weekTemplate) {
                Espo.Ui.error('Шаблон не обрано');
                return;
            }
            const foramtedDate = this.mondayOfWeek.split('-').reverse().join('.');
            this.confirm({
                message: 'Створити на ' + foramtedDate + '\n Шаблон: ' + weekTemplate.name,
                confirmText: 'Створити',
            }).then(() => {
                return this.getCollectionFactory().create('TrainingTemplate');
            })
            .then(collection => {
                collection.maxSize = 10000;
                collection.where = [{
                    "type": "equals",
                    "attribute": "weekTemplateId",
                    "value": this.weekTemplateId
                }];
                return collection.fetch();
            })
            .then(trainingTemplates => {
                console.log(trainingTemplates);
                this.createTrainingsFromTemplates(trainingTemplates.list);
                this.reRender();
            })
            .catch((error) => {
                console.error(error);
            })
        },

        createTrainingsFromTemplates: function(allTemplates) {
            let weekDayDate = this.mondayOfWeek;
            const weekDayNames = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"];
            weekDayNames.forEach(weekDayName => {
                this.createTrainingsForWeekDay(weekDayName, weekDayDate, allTemplates);
                weekDayDate = this.getNextWeekDay(weekDayDate);
            });
        },

        createTrainingsForWeekDay: function(weekDayName, weekDayDate, allTemplates) {
            const teplatesForWeekDay = allTemplates.filter(template => template.weekDay === weekDayName);
            const trainings = teplatesForWeekDay.map(template => this.templateToTraining(template, weekDayDate)); 
            trainings.forEach(training => this.createTraining(training));
            this.isGenerated = true;
        },

        templateToTraining: function(trainingTemplate, weekDayDate) {
            return {
                hallId: trainingTemplate.hallId,
                groupId: trainingTemplate.groupId,
                Status: 'Planned',
                dateStart: this.formateDatetime(weekDayDate, trainingTemplate.startTime),
                dateEnd: this.formateDatetime(weekDayDate, trainingTemplate.endTime),
                assignedUserId: trainingTemplate.trainerId
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
            fetch('/api/v1/Training', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(training)
            })
                .then(response => response.json())
                .then(training => {
                    console.log('trainings:');
                    console.log(training);
                })
                .finally(() => {})
                .catch(error => {
                    console.error(error);
                });
        },

        afterRender: function () {
            this.$el.find(`#weekTemplate`)[0].value = this.weekTemplateId;
        }
    })
});