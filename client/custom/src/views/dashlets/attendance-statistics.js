    define('custom:views/dashlets/attendance-statistics', ['views/dashlets/abstract/base'],  function (Dep) {
    return Dep.extend({
        name: 'AttendanceStatistics',
        template: 'custom:dashlets/attendance-statistics',  

        setup: function() {
            this.date = new Date().toISOString().split('T')[0];
            this.dateSelected = this.date;
            this.trainerId = null;
            this.tainerName = null;
            this.trainers = [];


            this.teamsIds = this.getOption('teams');
            if (!this.teamsIds) {
                this.teamsIds = this.getUser().get('teamsIds');
            }

            this.wait(
                this.fetchTrainings(this.date, this.teamsIds)
            );

            this.addHandler('click', '.trainer', 'handleTrainer');
            this.addHandler('click', '[data-action]', 'handleAction');
            this.addHandler('change', '#date', 'handleChangeDate');
        },

        afterRender: function() {
            if (this.trainerId) {
                const selectedTrainer = this.$el.find(`li[data-id=${this.trainerId}]`)[0];
                if (selectedTrainer && (this.date === this.dateSelected)) {
                    selectedTrainer.classList.add('trainer-selected');
                }
            }
        },

        handleChangeDate: function(e) {
            this.date = e.target.value;
            this.fetchTrainings(this.date, this.teamsIds)
                .then(() => this.reRender());
        },

        handleAction: function(e) {
            this[e.target.dataset.action](e);
        },

        prevDay: function() {
            this.date = this.addDays(this.date, -1);
            this.fetchTrainings(this.date, this.teamsIds)
                .then(() => this.reRender());
        },

        nextDay: function() {
            this.date = this.addDays(this.date, 1);
            this.fetchTrainings(this.date, this.teamsIds)
                .then(() => this.reRender());
        },

        addDays: function(trainingDate, days) {
            const date = new Date(trainingDate);
            const nextDate = date.setDate(date.getDate() + days);
            const inKievTimezone = new Date(nextDate).toLocaleString('uk-Ua', { timeZone: 'Europe/Kiev' });
            return inKievTimezone.split(',')[0].split('.').reverse().join('-');  
        },

        handleTrainer: function(e) {
            this.trainerId = e.target.closest('.trainer').dataset.id;
            this.trainerName = e.target.closest('.trainer').dataset.name;

            this.fetchAttendStat(this.date, this.trainerId, this.teamsIds)
                .then(() => {
                    this.reRender();
                });
        },

        fetchTrainings: async function(date, teamsIds) {
            try {
                const teamsParam = 'teams=' + teamsIds.join('&');
                let trainingsCollection = await this.getCollectionFactory().create('Training');
                trainingsCollection.where = [
                {
                    "type": "equals",
                    "attribute": "startDateOnly",
                    "value": date,
                }, 
                {
                    "type": "linkedWith",
                    "attribute": "teams",
                    "value": teamsIds
                }];
                const trainings = await trainingsCollection.fetch();

                this.trainers = this.createTrainers(trainings);
                return trainings;
            } catch (error) {
                console.error(error);
            }
        },

        createTrainers: function(trainings) {
            const trainers = [];
            trainings.list.forEach(training => {
                const trainer = trainers.find(trainer => trainer.id === training.assignedUserId);
                if (trainer) {
                    trainer.groupsHTML += `<span class="label label-default">${training.groupName}</span> `;
                } else {
                    trainers.push({
                        id: training.assignedUserId,
                        name: training.assignedUserName,
                        groupsHTML: `<span class="label label-default">${training.groupName}</span> `
                    });  
                }  
            });

            return trainers;
        },
        
        fetchAttendStat: async function(date, trainerId, teamsIds) {
            try {
                const teamsParam = 'teams=' + teamsIds.join('&');
                let stat = await fetch(`api/v1/Attendance/statistics/${date}/${trainerId}/${teamsParam}`);
                stat = await stat.json();

                this.dateSelected = stat.date;
                this.groups = stat.groups;
                this.attandanceTotalCount = this.groups.reduce(
                    (totalCount, group) => totalCount + group.attendanceCount, 0
                );

                return stat;
            } catch (error) {
                console.error(error);
            }
        },

        data: function() {
            return {
                date: this.date,
                dateSelected: this.dateSelected.split('-').reverse().join('.'),
                trainerId: this.trainerId,
                trainerName: this.trainerName,
                trainers: this.trainers,
                trainersCount: this.trainers.length,
                groups: this.groups,
                attandanceTotalCount: this.attandanceTotalCount
            }
        }
    })
});