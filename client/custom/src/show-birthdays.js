define('custom:show-birthdays', ['action-handler'], function (Dep) {
    return Dep.extend({
        initBirthdays: function () {
        },

        actionShowBirthdays: async function (data, e) {
            try {
                this.view.date = new Date().toLocaleDateString().split('.').reverse().join('-');
                this.view.year = this.view.date.split('-')[0];
                this.view.month = this.view.date.split('-')[1];

                await Espo.loader.requirePromise('fullcalendar');
                const birthdays = await this.getBirthdays(this.view.month);
                
                this.view.createView('dialog', 'views/modal', {
                    templateContent: `<div class="calendar-container"></div>`,
                    headerText: `Дні народження`,
                    backdrop: true,
                }, viewElem => {
                    viewElem.render();

                    viewElem.$el.find(`.modal-body`)[0].classList.add('marks-calendar-bg');//change bg-color
                    calendarElement = viewElem.$el.find(`.calendar-container`)[0];
                    this.createMarksCalendar(calendarElement, birthdays);
                });
            } catch (error) {
                this.handleError(error);
            }  
        },

        getBirthdays: async function(month) {
            try {
                const collection = await this.view.getCollectionFactory().create('Contact');
                collection.maxSize = 250;
                collection.where = [{
                    'type': 'like',
                    'attribute': 'birthday',
                    'value': `%-${month}-%`
                }];
                const contacts = await collection.fetch();
                if (contacts.total > 250) {
                    Espo.Ui.notify('Велика кількісь записів!<br>Не всі данні відображені', 'error', 5000);
                }
                
                return contacts.list.map(contact => {
                    let birthday = contact.birthday.split('-');
                    birthday[0] = this.view.year;
                    birthday = birthday.join('-');

                    return {
                        id: contact.id,
                        title: contact.name,
                        start: birthday
                    }
                });
            } catch(error) {
                this.handleError(error)
            }
        },

        createMarksCalendar: function(calendarElement, birthdays) {
            const showContact = function(contactId) {
                let options = { scope: 'Contact', id: contactId };
                this.view.createView('contactView', 'views/modals/detail', options, viewEl => {
                    viewEl.render();
                });
            }
            const prev = async function() {
                const birthdays = await this.getBirthdays(this.prevMonth(this.view.date));
                calendar.batchRendering(() => {
                    calendar.getEvents().forEach(event => event.remove());
                    birthdays.forEach(event => calendar.addEvent(event));
                    calendar.prev();
                });
            }
            const next = async function() {
                const birthdays = await this.getBirthdays(this.nextMonth(this.view.date));
                calendar.batchRendering(() => {
                    calendar.getEvents().forEach(event => event.remove());
                    birthdays.forEach(event => calendar.addEvent(event));
                    calendar.next();
                });
            }
            const prevFn = prev.bind(this);
            const nextFn = next.bind(this);
            const showContactFn = showContact.bind(this);

            const calendar = new window.FullCalendar.Calendar(calendarElement, {
                firstDay: 1,
                locale: 'ua',
                showNonCurrentDates: false,
                initialDate: this.view.today,
                events: birthdays,
                eventColor: 'darkgreen',
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    meridiem: false
                },
                eventClick: (info) => showContactFn(info.event.id),//Espo.Ui.notify(info.event.title, 'success', 0, { closeButton: true }),
                customButtons: {
                    prev: {
                      text: 'Prev',
                      click: prevFn
                    },
                    next: {
                      text: 'Next',
                      click: nextFn
                    },
                },
            });
            calendar.render();
        },

        nextMonth: function(date) {
            const current = new Date(date);
            const dateNextMonth = new Date(current.getFullYear(), current.getMonth() + 1, current.getDate());
            this.view.date = new Date(dateNextMonth).toLocaleDateString().split('.').reverse().join('-');
            this.view.year = this.view.date.split('-')[0]
            this.view.month = this.view.date.split('-')[1];
            return this.view.month;
        },

        prevMonth: function(date) {
            const current = new Date(date);
            const datePrevMonth = new Date(current.getFullYear(), current.getMonth() - 1, current.getDate());
            this.view.date = new Date(datePrevMonth).toLocaleDateString().split('.').reverse().join('-');
            this.view.year = this.view.date.split('-')[0];
            this.view.month = this.view.date.split('-')[1];
            return this.view.month;
        },

        handleError: function(error) {
            Espo.Ui.notify('Помилка', 'error', 2000);
            console.error(error);
        }
    });
 });