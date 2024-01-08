define('custom:views/attendance-sheet/abonements-panel', ['view'],  function (Dep, Model) {
    return Dep.extend({
        name: 'abonements-panel',
        template: 'custom:attendance-sheet/abonements-panel',

        setup: function() {
            this.today = new Date().toLocaleDateString().split('.').reverse().join('-');
            this.abonements = { list: [], total: 0 };
            this.trainingId = null;
            this.groupId = null;
            this.groupName = null;
            this.marksTotal = 0;
            
            this.initHandlers();
        },

        initHandlers: function() {
            this.addHandler('click', '.form-checkbox', 'handleMark');
            this.addHandler('click', ".fa-exclamation-circle", 'handleShowNote');
            this.addHandler('click', ".abon-name", 'handleEditAbon');
            this.addHandler('click', ".btn-add", "handleAddTraining");
            this.addHandler('click', ".fa-calendar", "handleViewMarks");
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

        handleAddTraining: function(e) {
            this[e.target.dataset.action](e);
        },

        addOneTime: async function(e) {
            this.createQuickActionModal('oneTimeAbonplanName');
        },

        addTrial: async function(e) {
            this.createQuickActionModal('trialAbonplanName');
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
                abonModel.defs.fields.isActivated.default = true;
                abonModel.defs.fields.isPaid.default = true;
                abonModel.defs.fields.isPaid.readOnly = true;
                abonModel.defs.fields.startDate.readOnly = false;

                let options = { scope: 'Abonement', model: abonModel };
                this.createView('quickCreate', 'views/modals/edit', options, view => {
                    view.render();
                    this.resetMetadata(abonModel);
                    this.showModalLoading(false);

                    this.listenToOnce(view, 'after:save', () => {
                        this.fetchAbonements(this.trainingId, this.groupId, this.groupName);
                    });
                });
            } catch (error) {
                this.handleError(error);
            }
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
                        this.fetchAbonements(this.trainingId, this.groupId, this.groupName);
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
            abonModel.defs.fields.isActivated.default = true;
            abonModel.defs.fields.isPaid.default = true;
            abonModel.defs.fields.isPaid.readOnly = true;

            abonModel.defs.fields.price.default = abonplan.price;
            
            abonModel.defs.fields.endDate.default = abonModel.defs.fields.startDate.default;
            
            abonModel.defs.fields.classCount.default = 1;
            abonModel.defs.fields.classesLeft.default = 1;
        },

        resetMetadata: function(abonModel) {
            delete abonModel.defs.fields.abonplan.readOnly;
            abonModel.defs.fields.abonplan.defaultAttributes = null;
            abonModel.defs.fields.groups.defaultAttributes = null;
            
            delete abonModel.defs.fields.isActivated.default;
            abonModel.defs.fields.isPaid.default = false;
            delete abonModel.defs.fields.isPaid.readOnly;

            delete abonModel.defs.fields.price.default;
            
            delete abonModel.defs.fields.endDate.default;
            
            delete abonModel.defs.fields.classCount.default;
            delete abonModel.defs.fields.classesLeft.default;
        },

        addFloatingMark: async function(e) {
            const abon = this.abonements.list.find(abon => abon.id === e.target.dataset.id);

            this.showModalLoading(true);
            try {
                const markModel = await this.getModelFactory().create('Mark');
                markModel.defs.fields.methodOfCreation.readOnly = true;
                markModel.defs.fields.abonement.readOnly = true;
                markModel.defs.fields.abonement.defaultAttributes = {
                    abonementId: abon.id,
                    abonementName: abon.name
                }

                let options = { scope: 'Mark', model: markModel };
                this.createView('quickCreate', 'views/modals/edit', options, view => {
                    view.render();
                    markModel.defs.fields.methodOfCreation.readOnly = false;
                    markModel.defs.fields.abonement.readOnly = false;
                    markModel.defs.fields.abonement.defaultAttributes = null;
                    this.showModalLoading(false);

                    this.listenToOnce(view, 'after:save', model => {
                        this.recalculateAbonement(abon.id)
                            .then(() => {
                                this.fetchAbonements(this.trainingId, this.groupId, this.groupName);
                            })
                            .catch(error => console.log(error));
                    });
                });
            } catch (error) {
                this.handleError(error);
            }
        },

        recalculate: async function(e) {
            const abon = this.abonements.list.find(abon => abon.id === e.target.dataset.id);

            this.recalculateAbonement(abon.id)
                .then(() => {
                    Espo.Ui.notify('Перераховано', 'success', 1000);
                });
        },

        /* public */
        fetchAbonements: function(trainingId, groupId, groupName) {
            this.trainingId = trainingId;
            this.groupId = groupId;
            this.groupName = groupName;

            this.isLoading(true);
            this.getCollectionFactory().create('Abonement')
                .then(collection => {
                    collection.maxSize = 10000;
                    collection.where = [{
                        "type": "linkedWith",
                        "attribute": "groups",
                        "value": [ groupId ],
                    }, {
                        "type": "greaterThanOrEquals",
                        "attribute": "endDate",
                        "value": this.today
                    }];
                    //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! START DATE (Pending) Don't forget!!!!
                    // isActive
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
                        "value": trainingId,
                    }];
                    return collection.fetch();
                })
                .then(marks => {
                    this.marksTotal = marks.total;
                    this.abonements = this.attachMarksToAbonements(this.abonements, marks.list);
                    this.reRender();
                    this.showNote()
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
            this.showModalLoading(true);
            const abon = this.abonements.list.find(abon => abon.id === e.target.dataset.id);
            
            let options = {
                attributes: {},
                scope: 'Abonement',
                id: abon.id
            };
            this.createView('abonEdit', 'views/modals/edit', options, view => {
                view.render();
                this.showModalLoading(false);
                
                this.listenToOnce(view, 'after:save', () => {
                    console.log(this);
                    this.fetchAbonements(this.trainingId, this.groupId, this.groupName);
                });
            });
        },

        handleViewMarks: function(e) {
            const abon = this.abonements.list.find(abon => abon.id === e.target.dataset.id);

            this.getCollectionFactory().create('Mark')
                .then(collection => {
                    collection.maxSize = 10000;
                    collection.where = [{
                        "type": "equals",
                        "attribute": "abonementId",
                        "value": abon.id 
                    }];
                    return collection.fetch();
                })
                .then(marks => {
                    this.createView('dialog', 'views/modal', {
                        templateContent: this.getMarksLayout(marks),
                        headerText: `Відмітки (ця функція в процесі розробки)`,
                        backdrop: true,
                        message: '',
                        buttonList: [
                            {
                                name: 'close',
                                label: this.translate('Close'),
                            }
                        ],
                    }, view => {
                        view.render();
                    });
                })
                .catch((error) => {
                    console.error(error);
                });
        },

        getMarksLayout: function(marks) {
            let layout = '';
            marks.list.forEach(mark => {
                layout += `<div>
                    <span class="label label-md label-warning">${mark.name}</span>
                    <span class="label label-md label-default">${mark.trainingName}</span>
                    <span class="label label-md label-default">${mark.assignedUserName}</span>
                </div>`
            });
            return layout ? layout : 'Ще немає';
        },

        handleShowNote: function(e) {
            const abon = this.abonements.list.find(abon => abon.id === e.target.dataset.id);
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
            if (abon.classesLeft <= 0) {
                Espo.Ui.error('В абонементі більше немає занять');
                this.$el.find(`input[data-abonement-id=${abonementId}]`)[0].checked = false;
                return;
            }
            if (abon.isFreezed) {
                Espo.Ui.error('Абонемент заморожено');
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
                .then(response => {
                    return response.json();
                })
                .then(mark => {
                    abon.mark = mark;
                    return this.recalculateAbonement(abon.id);
                })
                .then(() => {
                    abon.classesLeft = abon.classesLeft - 1;
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
                    this.reRender();
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
                abonementsTotal: this.abonements.total,
                marksTotal: this.marksTotal
            }
        },
    })
});