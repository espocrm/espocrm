/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/ 

Espo.define('Crm:Views.Calendar.Calendar', 'View', function (Dep) {
	
	return Dep.extend({

		template: 'crm:calendar.calendar',

		eventAttributes: [],

		colors: {
			'Meeting': '#558BBD',
			'Call': '#cf605d',
			'Task': '#76BA4E',
		},

		header: true,

		modeList: ['month', 'agendaWeek', 'agendaDay'],

		defaultMode: 'agendaWeek',
		
		slotMinutes: 30,

		titleFormat: {
			month: 'MMMM yyyy',
			week: 'MMM dd[ yyyy]{ -[ MMM] dd yyyy}',
			day: 'dddd, MMM dd, yyyy'
		},

		data: function () {
			return {
				mode: this.mode,
				modeList: this.modeList,
				header: this.header,
			};
		},

		init: function () {
			if (!('fullCalendar' in $)) {
				this.addReadyCondition(function () {
					return ('fullCalendar' in $);
				});
				Espo.loadLib('client/modules/crm/lib/fullcalendar.min.js', function () {
					this.tryReady();
				}.bind(this));
			}
		},

		events: {
			'click button[data-action="prev"]': function () {
				this.$calendar.fullCalendar('prev');
				this.updateDate();
			},
			'click button[data-action="next"]': function () {
				this.$calendar.fullCalendar('next');
				this.updateDate();
			},
			'click button[data-action="today"]': function () {
				this.$calendar.fullCalendar('today');
				this.updateDate();
			},
			'click button[data-action="mode"]': function (e) {
				var mode = $(e.currentTarget).data('mode');
				this.$el.find('button[data-action="mode"]').removeClass('active');
				this.$el.find('button[data-mode="' + mode + '"]').addClass('active');
				this.$calendar.fullCalendar('changeView', mode);
				this.updateDate();
			},
		},

		setup: function () {
			this.date = this.options.date || null;
			this.mode = this.options.mode || this.defaultMode;
			this.header = ('header' in this.options) ? this.options.header : this.header;
			this.slotMinutes = this.options.slotMinutes || this.slotMinutes;				
		},

		updateDate: function () {
			if (!this.header) {
				return;
			}				
			var view = this.$calendar.fullCalendar('getView');
			var today = new Date();
			if (view.start <= today && today < view.end) {
				this.$el.find('button[data-action="today"]').addClass('active');
			} else {
				this.$el.find('button[data-action="today"]').removeClass('active');
			}

			var map = {
				'agendaWeek': 'week',
				'agendaDay': 'day',
				'basicWeek': 'week',
				'basicDay': 'day',
			};

			var viewName = map[view.name] || view.name
			var title = $.fullCalendar.formatDates(view.start, view.end, this.titleFormat[viewName], {
				monthNames: this.translate('monthNames', 'lists'),
				monthNamesShort: this.translate('monthNamesShort', 'lists'),
				dayNames: this.translate('dayNames', 'lists'),
				dayNamesShort: this.translate('dayNamesShort', 'lists'),
			});
			this.$el.find('.date-title h4').text(title);
		},

		convertToFcEvent: function (o) {
			var event = {
				title: o.name,
				scope: o.scope,
				id: o.scope + '-' + o.id,
				recordId: o.id,
				dateStart: o.dateStart,
				dateEnd: o.dateEnd,
			};
			this.eventAttributes.forEach(function (attr) {
				event[attr] = o[attr];
			});
			if (o.dateStart) {
				event.start = this.getDateTime().toIso(o.dateStart);
			}
			if (o.dateEnd) {
				event.end = this.getDateTime().toIso(o.dateEnd);
			}
			if (!event.start || !event.end) {
				event.allDay = true;
				if (event.end) {
					event.start = event.end;
				}
			} else {
				var start = new Date(event.start);
				var end = new Date(event.end);
				if ((start.getDate() != end.getDate()) || (end - start >= 86400000)) {
					event.allDay = true;	
				} else {
					event.allDay = false;
					if (end - start < 1800000) {
						var m = this.getDateTime().toMoment(event.start).add('minutes', 30);
						event.end = m.format();
					}											
				}
			}

			event.color = this.colors[o.scope];
			return event;
		},

		convertToFcEvents: function (list) {
			var events = [];
			list.forEach(function (o) {
				event = this.convertToFcEvent(o);
				events.push(event)
			}.bind(this));
			return events;
		},
		
		convertTime: function (iso) {
			var format = this.getDateTime().internalDateTimeFormat;
			var timeZone = this.getDateTime().timeZone;
			var string = moment(iso).format(format);
			
			var m;
			if (timeZone) {	
				m = moment.tz(string, format, timeZone).utc();
			} else {
				m = moment.utc(string, format);
			}
			
			return m.format(format) + ':00';
		},

		afterRender: function () {
			var $calendar = this.$calendar = this.$el.find('div.calendar');

			var options = {
				header: false,
				axisFormat: this.getDateTime().timeFormat,
				timeFormat: this.getDateTime().timeFormat,
				defaultView: this.mode,
				weekNumbers: true,
				editable: true,
				aspectRatio: 1.62,
				selectable: true,
				selectHelper: true,
				ignoreTimezone: true,
				height: this.options.height || null,
				firstDay: this.getPreferences().get('weekStart'),
				select: function (start, end, allDay) {					
					var dateStart = this.convertTime(start);
					var dateEnd = this.convertTime(end);
										
					
					this.notify('Loading...');
					this.createView('quickEdit', 'Crm:Calendar.Modals.Edit', {
						attributes: {
							dateStart: dateStart,
							dateEnd: dateEnd,
						},
					}, function (view) {
						view.render();
						view.notify(false);
					});
					$calendar.fullCalendar('unselect');
				}.bind(this),
				eventClick: function (event) {
					this.notify('Loading...');
					this.createView('quickEdit', 'Crm:Calendar.Modals.Edit', {
						scope: event.scope,
						id: event.recordId
					}, function (view) {
						view.render();
						view.notify(false);
					});
				}.bind(this),
				viewRender: function (view, el) {
					var mode = view.name;
					var date = this.getDateTime().fromIso(this.$calendar.fullCalendar('getDate'));
					
					var m = moment(this.$calendar.fullCalendar('getDate'));
					this.trigger('view', m.format('YYYY-MM-DD'), mode);
				}.bind(this),
				events: function (from, to, callback) {					
					var fromServer = this.getDateTime().fromIso(from);
					var toServer = this.getDateTime().fromIso(to);
					
					this.fetchEvents(fromServer, toServer, callback);
				}.bind(this),
				eventDrop: function (event, dayDelta, minuteDelta, allDay) {
				
					var dateStart = this.convertTime(event.start) || null;
					var dateEnd = this.convertTime(event.end) || null;
					var attributes = {};
					if (!event.dateStart && event.dateEnd) {
						attributes.dateEnd = dateStart;
						event.dateEnd = attributes.dateEnd;
					} else {
						if (event.dateStart) {
							attributes.dateStart = dateStart;
							event.dateStart = dateStart;
						}
						if (event.dateEnd) {
							attributes.dateEnd = dateEnd;
							event.dateEnd = dateEnd;
						}
					}						
				
					if (!allDay) {
						if (!(event.dateStart && event.dateEnd)) {
							event.allDay = true;
						} else {
							var start = new Date(event.start);
							var end = new Date(event.end);
							if ((start.getDate() != end.getDate()) || (end - start >= 86400000)) {
								event.allDay = true;
							}
						}
						if (event.allDay) {
							this.$calendar.fullCalendar('renderEvent', event);
						}
					}												
					
					this.notify('Saving...');
					this.getModelFactory().create(event.scope, function (model) {
						model.once('sync', function () {
							this.notify(false);
						}.bind(this));
						model.id = event.recordId;
						model.save(attributes, {patch: true});
					}.bind(this));											
				}.bind(this),
				eventResize: function (event) {
					var attributes = {
						dateEnd: this.convertTime(event.end)
					};
					this.notify('Saving...');
					this.getModelFactory().create(event.scope, function (model) {
						model.once('sync', function () {
							this.notify(false);
						}.bind(this));
						model.id = event.recordId;
						model.save(attributes, {patch: true});
					}.bind(this));					
				}.bind(this),
				allDayText: '',
				firstHour: 8,
				columnFormat: {
					week: 'ddd dd',
					day: 'ddd dd',
				},
				monthNames: this.translate('monthNames', 'lists'),
				monthNamesShort: this.translate('monthNamesShort', 'lists'),
				dayNames: this.translate('dayNames', 'lists'),
				dayNamesShort: this.translate('dayNamesShort', 'lists'),
				weekNumberTitle: '',
			};

			if (this.date) {
				var dateArr = this.date.split('-');
				options.year = dateArr[0];
				options.month = dateArr[1] - 1;
				options.date = dateArr[2];
			}

			setTimeout(function () {
				$calendar.fullCalendar(options);					
				this.updateDate();
			}.bind(this), 150);
		},

		fetchEvents: function (from, to, callback) {
			$.ajax({
				url: 'Activities?from=' + from + '&to=' + to,				
				success: function (data) {
					var events = this.convertToFcEvents(data);
					callback(events);
					this.notify(false);
				}.bind(this)
			});		
		},

		addModel: function (model) {
			var d = model.attributes;
			d.scope = model.name;
			var event = this.convertToFcEvent(d);
			this.$calendar.fullCalendar('renderEvent', event);
		},

		updateModel: function (model) {
			var events = this.$calendar.fullCalendar('clientEvents', model.name + '-' + model.id);
			events.forEach(function (event) {
				var d = model.attributes;
				d.scope = model.name;
				var data = this.convertToFcEvent(d);
				for (var key in data) {
					event[key] = data[key];
				}
				this.$calendar.fullCalendar('updateEvent', event);
			}.bind(this));
		},
		
		removeModel: function (model) {
			this.$calendar.fullCalendar('removeEvents', model.name + '-' + model.id);
		},

	});
});

