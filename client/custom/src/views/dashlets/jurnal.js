define('custom:views/dashlets/jurnal', ['views/dashlets/abstract/base'],  function (Dep) {
    return Dep.extend({
        name: 'Journal',
        template: 'custom:dashlets/jurnal',

        setup: function() {
            this.isMarking = false;
            this.activities = [];
            this.abonements = [];
            this.halls = [];
            this.marksTotal = 0;
            this.selectedActivityId = null;
            this.activityHall = 'all';
            this.activityDate = new Date().toLocaleDateString().split('.').reverse().join('-');
            this.today = this.activityDate;

            console.log('test');
            
            this.wait(
                this.getCollectionFactory().create('Training')
                    .then(collection => {
                        collection.maxSize = 10000;
                        collection.where = [{
                            "type": "equals",
                            "attribute": "startDateOnly",
                            "value": this.activityDate
                        }, {
                            "type": "equals",
                            "attribute": "branchName",
                            "value": this.getOption("branch")
                        }];
                        return collection.fetch();
                    })
                    .then(records => {
                        this.activities = records;
                        this.activities.list.forEach(activity => this.foramteTimeDuration(activity));
                        this.sortByTimeDuration(this.activities.list);
                    })
                    .catch((error) => {
                        console.error(error);
                    })
            );

            this.wait(
                this.getCollectionFactory().create('Hall')
                .then(collection => {
                    collection.maxSize = 10000;
                    collection.where = [{
                        "type": "equals",
                        "attribute": "branchName",
                        "value": this.getOption('branch')
                    }];
                    return collection.fetch();
                })
                .then(halls => {
                    this.halls = halls;
                })
                .catch((error) => {
                    console.error(error);
                })
            );

            this.initHandlers();
        },
        
        initHandlers: function() {
            this.addHandler('click', '.activity', 'chooseActivitie');
            this.addHandler('click', 'td[data-mark-id]', 'handleMark');
            this.addHandler('change', '#date', 'handleDate');
            this.addHandler('change', '#hall', 'handleHall');
            this.addHandler('click', ".fa-exclamation-circle", 'handleShowNote');
            this.addHandler('click', ".abon-name", 'handleEditNote');
            this.markColorHandlers();
        },

        markColorHandlers: function() {
            this.addHandler('mouseover', 'td[data-mark-id]', (e) => e.target.style.backgroundColor = 'darkseagreen');
            this.addHandler('mouseout', 'td[data-mark-id]', (e) => e.target.style.backgroundColor = null);
            this.addHandler('mouseover', '.fa-check', (e) => e.target.parentElement.style.backgroundColor = 'darkseagreen');
        },

        afterRender: function () {
            this.$el.find(`option[value=${this.activityHall}]`)[0].selected = true;
            this.highlightMarkedAbonements();
            if (this.selectedActivityId) {
                const activityTableRow = this.$el.find(`tr[data-training-id=${this.selectedActivityId}]`)[0];
                activityTableRow.style.backgroundColor = '#d3d3d373';
                activityTableRow.style.fontWeight = 'bold'; 
            }
        },

        data: function() {
            return {
                halls: this.halls.list,
                activities: this.activities.list,
                activitiesTotal: this.activities.total,
                abonements: this.abonements.list,
                abonementsTotal: this.abonements.total || 0,
                marksTotal: this.marksTotal,
                activityDate: this.activityDate,
                activityHall: this.activityHall
            }
        },

        handleEditNote: function(e) {
            const abon = this.abonements.list.find(abon => abon.id === e.target.dataset.id);
            
            this.createView('dialog', 'views/modal', {
                templateContent: `<textarea id="dialog-note" placeholder="Нагадування не встановлене" maxlength="120" style="background-color: transparent; width: 100%; height: 90%; border: none">${abon.note || ''}</textarea>`,
                headerText: 'Нагадування для: ' + abon.contactName,
                backdrop: true,
                message: '',
                buttonList: [
                    {
                        name: 'saveNote',
                        label: 'Зберегти',
                        style: 'primary',
                        onClick: (view) => {
                            const note = document.getElementById('dialog-note').value;
                            this.updateNote(view, abon, note);
                        },
                    },
                    {
                        name: 'deleteNote',
                        label: 'Видалити',
                        style: 'danger',
                        onClick: (view) => {
                            this.updateNote(view, abon, '');
                        },
                    },
                    {
                        name: 'close',
                        label: this.translate('Close'),
                    }
                ],
            }, view => {
                view.render();
            });
        },

        updateNote: function(view, abon, note) {
            fetch('/api/v1/Abonement/' + abon.id, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ note })
            })
                .then(response => response.json())
                .then(updatedAbon => {
                    abon.note = updatedAbon.note;
                    this.reRender();
                    view.onRemove();
                })
                .catch(err => console.error(err));
        },

        handleShowNote: function(e) {
            const abon = this.abonements.list.find(abon => abon.id === e.target.dataset.id);
            if (!abon?.note) {
                Espo.Ui.notify('Помилка: нагадування не знайдено', 'error', 2000);
                return;
            }
            Espo.Ui.notify(abon.note, 'warning', 0, { closeButton: true });
        },

        handleHall: function(e) {
            this.activityHall = e.target.value;
            this.filterActivities();
        },

        handleDate: function(e) {
            this.activityDate = e.target.value;
            this.filterActivities();
        },

        handleMark: function(e) {
            if (this.isMarking) return;//block mark button

            if (e.target.dataset.markId) {
                this.deleteMark(e.target.dataset.markId);  
            } else {
                this.createMark(e.target.dataset.abonementId);
            }
        },

        createMark: function(abonementId) {
            const abon = this.abonements.list.find(abon => abon.id === abonementId)
            if (abon.classesLeft <= 0) {
                Espo.Ui.error('В абонементі більше немає занять');
                return;
            }
            this.isMarking = true;
            fetch('/api/v1/Mark', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    abonementId,
                    trainingId: this.selectedActivityId,
                    methodOfCreation: "Jurnal"
                })
            })
                .then(response => response.json())
                .then(mark => {
                    abon.classesLeft = abon.classesLeft - 1;
                    abon.mark = mark;
                    this.marksTotal++;
                    this.reRender();
                    this.recalculateAbonement(abon.id);
                })
                .finally(() => this.isMarking = false)
                .catch(error => {
                    console.error(error);
                });
        },

        deleteMark: function(markId) {
            const abon = this.abonements.list.find(abon => abon.mark.id == markId);
            this.isMarking = true;
            fetch(`/api/v1/Mark/${markId}`, {
                method: 'DELETE',
            })  
                .then(response => response.json())
                .then(mark => {
                    abon.classesLeft = abon.classesLeft + 1;
                    abon.mark = {};
                    this.marksTotal--;
                    this.reRender();
                    this.recalculateAbonement(abon.id);
                })
                .finally(() => this.isMarking = false)
                .catch(error => {
                    console.error(error);
                });
        },
        
        highlightMarkedAbonements: function() {
            const checkElements = this.$el.find('.fa-check');
            for (let i = 0; i < checkElements.length; i++) {
                checkElements[i].closest('tr').style.backgroundColor = '#d3d3d373';
            }
        },

        chooseActivitie: function(e) {
            const trainingId = e.target.parentElement.dataset.trainingId;
            const groupId = e.target.parentElement.dataset.groupId;
            this.selectedActivityId = trainingId;
            
            this.getCollectionFactory().create('Abonement')
                .then(collection => {
                    collection.maxSize = 10000;
                    collection.where = [{
                        "type": "linkedWith",
                        "attribute": "groups",
                        "value": [groupId],
                    }, {
                        "type": "greaterThanOrEquals",
                        "attribute": "endDate",
                        "value": this.today
                    }];
                    return collection.fetch();
                })
                .then((abonements) => {
                    this.getCollectionFactory().create('Mark')
                        .then(collection => {
                            collection.maxSize = 10000;
                            collection.where = [{
                                "type": "equals",
                                "attribute": "trainingId",
                                "value": trainingId,
                            }];
                            return collection.fetch();
                        })
                        .then(marks => {
                            this.marksTotal = marks.total;
                            this.abonements = this.attachMarksToAbonements(abonements, marks.list);
                            this.reRender();
                        })        
                        .then(() => {
                            let note = '';
                            this.abonements.list.forEach(abon => {
                                if (abon.note) {
                                    note += '<b>' + abon.contactName + '</b><br>';
                                    note += abon.note + '<br><br>';
                                }
                            });
                            if (note) {
                                note = 'НАГАДУВАННЯ!<br><br>' + note;
                                Espo.Ui.notify(note, 'warning', 0, { closeButton: true });
                            }
                        });             
                })
                .catch((error) => {
                    console.error(error);
                })
          
        },

        attachMarksToAbonements: function(abonements, marks) {
            const abonsWithMarks = { ...abonements };
            
            abonsWithMarks.list.forEach(abon => {
                const markForAbon = marks.find(mark => abon.id === mark.abonementId)
                abon.mark = { ...markForAbon };
            });

            return abonsWithMarks;
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
                    const conditionBranch = {
                        "type": "equals",
                        "attribute": "branchName",
                        "value": this.getOption("branch")
                    };
                    
                    const conditions = [ conditionDate, conditionBranch ];
                    if (this.activityHall !== 'all') {
                        conditions.push(conditionHall);
                    }

                    collection.maxSize = 10000;
                    collection.where = conditions;
                    return collection.fetch();
                })
                .then(records => {
                    this.activities = records;
                    this.activities.list.forEach(activity => this.foramteTimeDuration(activity));
                    this.sortByTimeDuration(this.activities.list);
                    this.reRender();
                })
                .catch((error) => {
                    console.error(error);
                });
        },

        setActivities: function(records) {
            this.activities = records;
            this.activities.list.forEach(activity => this.foramteTimeDuration(activity));
            this.sortByTimeDuration(this.activities.list);
        },

        foramteTimeDuration: function(activity) {
            const dateStart = this.convertUTCToLocal(activity.dateStart);
            const dateEnd = this.convertUTCToLocal(activity.dateEnd);
            activity.timeDuration = this.getTimeOnly(dateStart) + " - " + this.getTimeOnly(dateEnd);
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

        recalculateAbonement: function(abonementId) {
            const payload = {
                action: "recalculateFormula",
                entityType: "Abonement",
                idle: false,
                params: {
                    ids: [abonementId]
                }
            };

            return fetch('/api/v1/MassAction', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
        }
    })
});