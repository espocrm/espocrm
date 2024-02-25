define('custom:views/attendance-sheet/abonements-panel', ['view'],  function (Dep, Model) {
    return Dep.extend({
        name: 'abonements-panel',
        template: 'custom:attendance-sheet/abonements-panel',

        setup: function() {
            this.today = new Date().toLocaleDateString().split('.').reverse().join('-');
            this.abonements = { list: [], total: 0 };
            this.otherGroupsAbons = { list: [], total: 0 };
            this.trainingId = null;
            this.training = null;
            this.groupId = null;
            this.groupName = null;
            this.marksTotal = 0;
            this.marks = { list: [], total: 0 };
            
            Espo.loader.requirePromise('fullcalendar');
            this.initHandlers();
        },

        initHandlers: function() {
            this.addHandler('click', ".mark", 'handleMark');
            this.addHandler('click', ".fa-sticky-note", 'handleShowNote');
            this.addHandler('click', ".abon-name", 'handleEditAbon');
            this.addHandler('click', ".other-group", 'handleEditAbonOtherGroups');
            this.addHandler('click', ".btn-add", "handleAddButton");
            this.addHandler('click', ".btn-floating-mark", "createFloatingMark");
            this.addHandler('click', ".fa-calendar", "handleViewMarks");
            this.addHandler('click', ".floating-view", "handleViewFloatingMarks");
            this.addHandler('click', ".floating-mark", "deleteFloatingMark");
            this.addHandler('click', ".abon-activate", 'abonementActivate');
        },

        afterRender: function () {
            this.highlightMarkedAbonements();
        },

        highlightMarkedAbonements: function() {
            const checkElements = this.$el.find(".form-checkbox[checked]");
            for (let i = 0; i < checkElements.length; i++) {
                checkElements[i].closest('tr').classList.add("text-muted");
            }
        },

        handleAddButton: function(e) {
            this[e.target.dataset.action](e);
        },

        addOneTime: async function(e) {
            this.createQuickActionModal('oneTimeAbonplanName');
        },

        addTrial: async function(e) {
            this.createQuickActionModal('trialAbonplanName');
        },

        createQuickActionModal: async function(abonplanNameFromCS) {
            this.showModalLoading(true);
            try {
                const abonplans = await this.fetchAbonplansByCSName(abonplanNameFromCS);
                const abonModel = await this.getModelFactory().create('Abonement');
                if (abonplans.total === 1) {
                    this.prepareMetadata(abonModel, abonplans.list[0], this.groupId, this.groupName);
                }
                let options = { scope: 'Abonement', model: abonModel };
                this.createView('quickCreate', 'views/modals/edit', options, view => {
                    view.render();
                    this.resetMetadata(abonModel);
                    this.showModalLoading(false);

                    this.listenToOnce(view, 'after:save', () => {
                        this.fetchAbonements(this.training, this.groupId, this.groupName);
                    });
                });
            } catch (error) {
                this.handleError(error);
            }
        },

        addAbonement: async function(e) {
            this.showModalLoading(true);
            try {
                const abonModel = await this.getModelFactory().create('Abonement');
                abonModel.defs.fields.groups.defaultAttributes = {
                    groupsIds: [ this.groupId ],
                    groupsNames: {
                        [this.groupId]: this.groupName
                    }
                };
                abonModel.defs.fields.fromAttendanceSheet.default = true;
                abonModel.defs.fields.isPaid.default = true;
                abonModel.defs.fields.isPaid.readOnly = true;

                let options = { scope: 'Abonement', model: abonModel };
                this.createView('quickCreate', 'views/modals/edit', options, view => {
                    view.render();
                    this.resetMetadata(abonModel);
                    this.showModalLoading(false);

                    this.listenToOnce(view, 'after:save', () => {
                        this.fetchAbonements(this.training, this.groupId, this.groupName);
                    });
                });
            } catch (error) {
                this.handleError(error);
            }
        },

        fetchAbonplansByCSName: async function(abonplanNameFromCS) {
            const customSettingsCollection = await this.getCollectionFactory().create('CustomSettings');
                customSettingsCollection.where =  [{
                    "type": "equals",
                    "attribute": "name",
                    "value": abonplanNameFromCS,
                }];
                const customSettings = await customSettingsCollection.fetch();
                
                const abonplansCollection = await this.getCollectionFactory().create('Abonplan');
                abonplansCollection.where =  [{
                    "type": "equals",
                    "attribute": "name",
                    "value": customSettings.list[0].value,
                }];
                //if superadmin, reduce by teamIds
                if (this.getUser().attributes.type === 'admin') {
                    abonplansCollection.where.push({
                        "type": "linkedWith",
                        "attribute": "teams",
                        "value": this.getUser().attributes.teamsIds
                    });
                }
                const abonplans = await abonplansCollection.fetch();
                return abonplans;
        },

        prepareMetadata: function(abonModel, abonplan, groupId, groupName) {
            abonModel.defs.fields.abonplan.readOnly = true;
            abonModel.defs.fields.abonplan.defaultAttributes = {
                abonplanId: abonplan.id,
                abonplanName: abonplan.name
            };
            abonModel.defs.fields.groups.defaultAttributes = {
                groupsIds: [ groupId ],
                groupsNames: {
                    [groupId]: groupName
                }
            };
            abonModel.defs.fields.fromAttendanceSheet.default = true;
            abonModel.defs.fields.isPaid.default = true;
            abonModel.defs.fields.isPaid.readOnly = true;

            abonModel.defs.fields.price.default = abonplan.price;
            
            abonModel.defs.fields.classCount.default = abonplan.classCount;
            abonModel.defs.fields.classesLeft.default = abonplan.classCount;
        },

        resetMetadata: function(abonModel) {
            delete abonModel.defs.fields.abonplan.readOnly;
            abonModel.defs.fields.abonplan.defaultAttributes = null;
            abonModel.defs.fields.groups.defaultAttributes = null;
            
            delete abonModel.defs.fields.fromAttendanceSheet.default;
            abonModel.defs.fields.isPaid.default = false;
            delete abonModel.defs.fields.isPaid.readOnly;

            delete abonModel.defs.fields.price.default;
            
            delete abonModel.defs.fields.endDate.default;
            
            delete abonModel.defs.fields.classCount.default;
            delete abonModel.defs.fields.classesLeft.default;
        },

        abonementActivate: function (e) {
            const abon = this.abonements.list.find(abon => abon.id === e.target.dataset.id);
            if (!abon.isPaid) {
                Espo.Ui.notify('Абонемент не сплачений', 'error', 2000);
                return;
            }

            const formatedStartDate = abon.startDate.split('-').reverse().join('.');
            this.confirm({
                message: `Дата початку: ${ formatedStartDate }`,
                confirmText: 'Активувати', // text of the confirmation button
            }).then(() => {
                this.activateAbonement(abon.id)
                    .then(abonUpdated => {
                        abon.isActivated = true;
                        abon.isNotActivated = false;
                        abon.endDate = abonUpdated.endDate;
                        this.setAbonStatus(abon);
                        this.reRender();
                    })
                    .catch(err => {
                        this.handleError(error);
                    });
            });
        },

        activateAbonement: function(abonementId) {
            return fetch('/api/v1/Abonement/' + abonementId, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ 
                        isActivated: true
                    })
                })
                .then(responce => {
                    return responce.json();
                })
        },

        createFloatingMark: async function(e) {
            this.showModalLoading(true);
            try {
                const markModel = await this.getModelFactory().create('Mark');
                markModel.defs.fields.isOtherGroup.default = true;
                markModel.defs.fields.methodOfCreation.readOnly = true;
                markModel.defs.fields.training.readOnly = true;
                markModel.defs.fields.training.readOnly = true;
                markModel.defs.fields.training.defaultAttributes = {
                    trainingId: this.trainingId,
                    trainingName: this.groupName
                }
                
                let options = { scope: 'Mark', model: markModel };
                this.createView('quickCreate', 'views/modals/edit', options, view => {
                    view.render();
                    markModel.defs.fields.isOtherGroup.default = false;
                    markModel.defs.fields.methodOfCreation.readOnly = false;
                    markModel.defs.fields.training.readOnly = false;
                    markModel.defs.fields.training.defaultAttributes = null;
                    this.showModalLoading(false);

                    this.listenToOnce(view, 'after:save', (mark) => {
                        this.recalculateAbonement(mark.attributes.abonementId)
                            .then(() => {
                                this.fetchAbonements(this.training, this.groupId, this.groupName);
                            });
                    });
                });
            } catch (error) {
                this.handleError(error);
            }
        },

        recalculate: async function(e) {
            const abon = this.abonements.list.find(abon => abon.id === e.target.dataset.id);

            this.recalculateAbonement(abon.id)
                .then(responce => responce.json())
                .then(abonUpdated => {
                    abon.classesLeft = abonUpdated.classesLeft;
                    Espo.Ui.notify('Заняття перераховані', 'success', 1000);
                    this.reRender();
                });
        },

        recalculateOther: async function(e) {
            const abon = this.otherGroupsAbons.list.find(abon => abon.id === e.target.dataset.id);
            
            this.recalculateAbonement(abon.id)
                .then(responce => responce.json())
                .then(abonUpdated => {
                    abon.classesLeft = abonUpdated.classesLeft;
                    Espo.Ui.notify('Заняття перераховані', 'success', 1000);
                    this.reRender();
                });
        },

        /* public */
        fetchAbonements: function(training, groupId, groupName) {
            this.trainingId = training.id;
            this.training = training;
            this.groupId = groupId;//fix: add from training
            this.groupName = groupName;//fix: add from training
            this.isLoading(true);
            this.getCollectionFactory().create('Abonement')
                .then(collection => {
                    collection.maxSize = 10000;
                    collection.where = [
                    {
                        "type": "linkedWith",
                        "attribute": "groups",
                        "value": [ groupId ],
                    }, 
                    {
                        "type": "or",
                        "value": [
                            {
                                "type": "lessThanOrEquals",
                                "attribute": "startDate",
                                "value": training.startDateOnly
                            },
                            {
                                "type": "isFalse",
                                "attribute": "isActivated"
                            }
                        ]
                    },
                    {
                        "type": "or",
                        "value": [
                            {
                                /* all abons which have endDate, so isActivated = true */
                                "type": "greaterThanOrEquals",
                                "attribute": "endDate",
                                "value": training.startDateOnly
                            },
                            {
                                "type": "isTrue",
                                "attribute": "isFreezed"
                            },
                            {   /* dont have end date */
                                "type": "isFalse",
                                "attribute": "isActivated"
                            }
                        ]
                    }];
                    return collection.fetch();
                })
                .then((abonements) => {
                    this.abonements = abonements;
                    return this.getCollectionFactory().create('Mark')
                })
                .then(collection => {
                    collection.maxSize = 10000;
                    collection.where = [{
                        "type": "equals",
                        "attribute": "trainingId",
                        "value": this.trainingId,
                    }];
                    return collection.fetch();
                })
                .then(marks => {
                    this.marksTotal = marks.total;
                    this.marks = marks;
                    this.abonements = this.attachMarksToAbonements(this.abonements, marks.list);
                    
                    return this.fetchOtherAbons(this.abonements.list, marks.list);
                })
                .then(otherAbons => {
                    this.otherGroupsAbons = this.attachMarksToAbonements(otherAbons, this.marks.list);
                    this.setAbonsStatus(this.abonements.list);
                    this.setAbonsStatus(this.otherGroupsAbons.list);
                    this.reRender();
                    this.showNote();
                })
                .finally(() => this.isLoading(false))
                .catch((error) => {
                    this.handleError(error);
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

        fetchOtherAbons: function(abonements, marks) {
            const floatingMarkIds = this.getMarksFromOtherGroups(abonements, marks);
            if (floatingMarkIds.length) {
                return this.fetchAbonementsFromOtherGroups(floatingMarkIds);
            }

            return { list: [], total: 0 };
        },

        getMarksFromOtherGroups: function(abonements, marks) {
            const floatingMarkIds = [];

            marks.forEach(mark => {
                const abonFromOtherGroup = abonements.find(abon => abon.id === mark.abonementId)
                if (!abonFromOtherGroup) {
                    floatingMarkIds.push(mark.abonementId);
                }
            });

            return floatingMarkIds;
        },

        fetchAbonementsFromOtherGroups: async function(abonIds) {
            try {
                const collection = await this.getCollectionFactory().create('Abonement')
                collection.maxSize = 10000;
                collection.where = [{
                    "type": "in",
                    "attribute": "id",
                    "value": abonIds,
                }];
                const otherAbons = await collection.fetch();
                return otherAbons;
            } catch(error) {
                this.handleError(error);
            }
        },

        showNote: function() {
            let note = '';
            this.abonements.list.forEach(abon => {
                if (abon.note) {
                    note += abon.number + ' ' + abon.contactName + '<br>';
                    note += abon.note + '<br><br>';
                }
            });
            if (note) {
                Espo.Ui.notify(note, 'warning', 0, { closeButton: true });
            }
        },

        handleEditAbon: function(e) {
            const abon = this.abonements.list.find(abon => abon.id === e.target.dataset.id);
            this.createEditAbonModal(abon);
        },

        handleEditAbonOtherGroups: function(e) {
            const abon = this.otherGroupsAbons.list.find(abon => abon.id === e.target.dataset.id);
            this.createEditAbonModal(abon);
        },

        createEditAbonModal: function(abon) {
            this.showModalLoading(true);
            let options = { scope: 'Abonement', id: abon.id };
            this.createView('abonEdit', 'views/modals/edit', options, view => {
                view.render();
                this.showModalLoading(false);
                
                this.listenToOnce(view, 'after:save', () => {
                    this.fetchAbonements(this.training, this.groupId, this.groupName);
                });
            });
        },

        handleViewMarks: function(e) {
            const abon = this.abonements.list.find(abon => abon.id === e.target.dataset.id);
            this.viewMarks(abon);
        },

        handleViewFloatingMarks: function(e) {
            const abon = this.otherGroupsAbons.list.find(abon => abon.id === e.target.dataset.id);
            this.viewMarks(abon);
        },

        viewMarks: async function(abon) {
            try {
                const trainingEvents = await this.getTrainingsAsMarks(abon);
                const noMarksMsg =  trainingEvents.length ? '' : 'Відміток поки немає';
                
                this.createView('dialog', 'views/modal', {
                    templateContent: `<div class="calendar-container">${noMarksMsg}</div>`,
                    headerText: `Відмітки абонементу: ${ abon.name } | занять: ${abon.classCount} відміток: ${abon.classCount - abon.classesLeft}`,
                    backdrop: true,
                }, view => {
                    view.render();
                    if (!trainingEvents.length) return;

                    view.$el.find(`.modal-body`)[0].classList.add('marks-calendar-bg');//change bg-color
                    calendarElement = view.$el.find(`.calendar-container`)[0];
                    this.createMarksCalendar(calendarElement, trainingEvents);
                });
            } catch (error) {
                this.handleError(error);
            }
        },

        getTrainingsAsMarks: async function(abon) {
            try {
                const markCollection = await this.getCollectionFactory().create('Mark');
                markCollection.maxSize = 1000;
                markCollection.where = [{
                    "type": "equals",
                    "attribute": "abonementId",
                    "value": abon.id 
                }];
                const marks = await markCollection.fetch();

                const trainingsIds = marks.list.map(mark => mark.trainingId);
                
                const trainingCollection = await this.getCollectionFactory().create('Training');
                trainingCollection.maxSize = 1000;
                trainingCollection.where = [{
                    "type": "equals",
                    "attribute": "id",
                    "value": trainingsIds
                }];
                const trainings = await trainingCollection.fetch();

                const trainingEvents = trainings.list.map(training => {
                    return {
                        id: training.id,
                        title: training.groupName,
                        start: training.dateStart.split(' ').join('T'),
                    }
                });

                return trainingEvents;
            } catch(error) {
                this.handleError(error);
            }
        },

        createMarksCalendar: function(calendarElement, trainingEvents) {
            const calendar = new window.FullCalendar.Calendar(calendarElement, {
                firstDay: 1,
                locale: 'ua',
                eventClick: (info) => Espo.Ui.notify(info.event.title, 'success', 2000),
                initialDate: trainingEvents[trainingEvents.length - 1].start.split('T')[0],
                events: trainingEvents,
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    meridiem: false
                },
                eventColor: 'red'
            });

            calendar.render();
        },

        handleShowNote: function(e) {
            let abon = this.abonements.list.find(abon => abon.id === e.target.dataset.id);
            if (!abon) {
                abon = this.otherGroupsAbons.list.find(abon => abon.id === e.target.dataset.id);
            }
            if (!abon?.note) {
                Espo.Ui.notify('Помилка: нагадування не знайдено', 'error', 2000);
                return;
            }
            Espo.Ui.notify(abon.note, 'warning', 0, { closeButton: true });
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
            if (abon.isFreezed) {
                Espo.Ui.error('Абонемент заморожено');
                this.$el.find(`input[data-abonement-id=${abonementId}]`)[0].checked = false;
                return;
            }
            if (abon.isNotActivated) {
                Espo.Ui.error('Абонемент не активовано');
                this.$el.find(`input[data-abonement-id=${abon.id}]`)[0].checked = false;
                return;
            }
            if (abon.classesLeft <= 0) {
                Espo.Ui.error('В абонементі більше немає занять');
                this.$el.find(`input[data-abonement-id=${abonementId}]`)[0].checked = false;
                return;
            }
            this.isLoading(true);
            fetch('/api/v1/Mark', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    abonementId: abonementId,
                    trainingId: this.trainingId,
                    assignedUserId: this.getUser().id,
                    methodOfCreation: "Jurnal"
                })
            })
                .then(response => response.json())
                .then(mark => {
                    abon.mark = mark;
                    return this.recalculateAbonement(abon.id);
                })
                .then(() => {
                    abon.classesLeft = abon.classesLeft - 1;
                    this.setAbonStatus(abon);
                    this.marksTotal++;
                })
                .finally(() => {
                    this.isLoading(false);
                    this.reRender();
                })
                .catch(error => {
                    this.handleError(error);
                });
        },

        deleteMark: function(markId) {
            const abon = this.abonements.list.find(abon => abon.mark.id == markId);
            if (abon.isFreezed) {
                Espo.Ui.error('Абонемент заморожено');
                this.$el.find(`input[data-mark-id=${ markId }]`)[0].checked = true;
                return;
            }
            /*
            if (this.isOutdate(abon)) {
                Espo.Ui.error('Абонемент більше не діє: ' + abon.endDate);
                this.$el.find(`input[data-mark-id=${ markId }]`)[0].checked = true;
                return;
            }
            */
            this.isLoading(true);
            fetch(`/api/v1/Mark/${markId}`, {
                method: 'DELETE',
            })  
                .then(response => response.json())
                .then(mark => {
                    return this.recalculateAbonement(abon.id);
                })
                .then(() => {
                    abon.classesLeft = abon.classesLeft + 1;
                    abon.mark = {};
                    this.marksTotal--;
                    this.setAbonStatus(abon);
                })
                .finally(() => {
                    this.isLoading(false);
                    this.reRender();
                })
                .catch(error => {
                    this.handleError(error);
                });
        },

        deleteFloatingMark: function(e) {
            const markId = e.target.dataset.markId;
            const abon = this.otherGroupsAbons.list.find(abon => abon.mark.id == markId);
            /*
            if (this.isOutdate(abon)) {
                Espo.Ui.error('Абонемент більше не діє: ' + abon.endDate);
                this.$el.find(`input[data-mark-id=${ markId }]`)[0].checked = true;
                return;
            }
            */
            this.isLoading(true);
            fetch(`/api/v1/Mark/${markId}`, {
                method: 'DELETE',
            })  
                .then(response => response.json())
                .then(mark => {
                    this.marksTotal--;
                    return this.recalculateAbonement(abon.id);
                })
                .then(() => {
                    const abonIndex = this.otherGroupsAbons.list.findIndex(a => a.id == abon.id);
                    this.otherGroupsAbons.list.splice(abonIndex, 1);
                })
                .finally(() => {
                    this.isLoading(false);
                    this.reRender();
                })
                .catch(error => {
                    this.handleError(error);
                });
        },

        //trigger formula-script
        recalculateAbonement: function(abonementId) {
            return fetch('/api/v1/Abonement/' + abonementId, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    lastUpdate: new Date().toLocaleString()
                 })
            })
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

        setAbonsStatus: function(abons) {
            abons.forEach(abon => this.setAbonStatus(abon));
        },

        setAbonStatus: function(abon) {
            if (abon.classesLeft <= 0) {
                abon.isEmpty = true;
                abon.isActive = false;
            } else {
                abon.isEmpty = false;
                abon.isActive = true;
            }
            if (this.isOutdate(abon)) {
                abon.isOutdate = true;
                abon.isActive = false;
                abon.isEmpty = false;
            }
            if (!abon.isActivated) {
                abon.isNotActivated = true;
                abon.isActive = false;
            }
            if (abon.isFreezed) {
                abon.isOutdate = false;
                abon.isActive = false;
            }
        },

        isPending: function(abon) {
            const startDate = new Date(abon.startDate);
            const today = new Date(this.today);

            return today < startDate;
        },

        isOutdate: function(abon) {
            if (!abon.endDate) return false;//not activated=>not have endDate=>not outdated 
            
            const endDate = new Date(abon.endDate);
            const today = new Date(this.today);

            return today > endDate;
        },

        sortByTimeDuration: function(activities) {
            activities.sort((a, b) => {
                if (a.timeDuration > b.timeDuration) return 1;
                if (a.timeDuration < b.timeDuration) return -1;
                return 0;
            });
        },

        isLoading: function(isShown) {
            if (isShown) {
                this.$el.find("#loaderBackground")[0].style.visibility = 'visible';
                this.$el.find("#loaderSpinner")[0].style.visibility = 'visible';
            } else {
                this.$el.find("#loaderBackground")[0].style.visibility = 'hidden';
                this.$el.find("#loaderSpinner")[0].style.visibility = 'hidden';
            }
        },

        showModalLoading: function(isLoading) {
            if (isLoading) {
                Espo.Ui.notify('<span class="fas fa-spinner fa-spin">', 'warning', 20000, true);
            } else {
                Espo.Ui.notify('', 'warning', 1);
            }
        },

        handleError: function(error) {
            Espo.Ui.notify('Помилка', 'error', 2000);
            console.error(error);
        },

        data: function() {
            return {
                trainingId: this.trainingId,
                abonements: this.abonements.list,
                otherAbonements: this.otherGroupsAbons.list,
                abonementsTotal: this.abonements.total + this.otherGroupsAbons.total,
                marksTotal: this.marksTotal,
                groupName: this.groupName
            }
        },
    })
});