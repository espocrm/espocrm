/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

define('crm:views/meeting/fields/reminders', ['views/fields/base', 'ui/select', 'lib!moment'],
function (Dep, /** module:ui/select*/Select, moment) {

    return Dep.extend({

        dateField: 'dateStart',

        detailTemplate: 'crm:meeting/fields/reminders/detail',
        listTemplate: 'crm:meeting/fields/reminders/detail',
        editTemplate: 'crm:meeting/fields/reminders/edit',

        events: {
            'click [data-action="addReminder"]': function () {
                let type = this.getMetadata().get('entityDefs.Reminder.fields.type.default');
                let seconds = this.getMetadata().get('entityDefs.Reminder.fields.seconds.default') || 0;

                let item = {
                    type: type,
                    seconds: seconds,
                };

                this.reminderList.push(item);

                this.addItemHtml(item);
                this.trigger('change');

                this.focusOnButton();
            },
            'click [data-action="removeReminder"]': function (e) {
                let $reminder = $(e.currentTarget).closest('.reminder');
                let index = $reminder.index();

                $reminder.remove();

                this.reminderList.splice(index, 1);

                this.focusOnButton();
            },
        },

        getAttributeList: function () {
            return [this.name];
        },

        setup: function () {
            if (this.model.isNew() && !this.model.get(this.name) && this.model.entityType !== 'Preferences') {
                this.reminderList = this.getPreferences().get('defaultReminders') || [];
            } else {
                this.reminderList = this.model.get(this.name) || [];
            }

            this.reminderList = Espo.Utils.cloneDeep(this.reminderList);

            this.listenTo(this.model, 'change:' + this.name, () => {
                this.reminderList = Espo.Utils
                    .cloneDeep(this.model.get(this.name) || []);
            });

            this.typeList = Espo.Utils
                .clone(this.getMetadata().get('entityDefs.Reminder.fields.type.options') || []);
            this.secondsList = Espo.Utils
                .clone(this.getMetadata().get('entityDefs.Reminder.fields.seconds.options') || []);

            this.dateField = this.model.getFieldParam(this.name, 'dateField') || this.dateField;

            this.listenTo(this.model, 'change:' + this.dateField, (m, v, o) => {
                if (this.isEditMode()) {
                    this.reRender();
                }
            });
        },

        afterRender: function () {
            if (this.isEditMode()) {
                this.$container = this.$el.find('.reminders-container');

                this.reminderList.forEach(item => {
                    this.addItemHtml(item);
                });
            }
        },

        focusOnButton: function () {
            this.$el.find('button[data-action="addReminder"]')
                .get(0)
                .focus({preventScroll: true});
        },

        updateType: function (type, index) {
            this.reminderList[index].type = type;
            this.trigger('change');
        },

        updateSeconds: function (seconds, index) {
            this.reminderList[index].seconds = seconds;
            this.trigger('change');
        },

        addItemHtml: function (item) {
            let $item = $('<div>').addClass('input-group').addClass('reminder');

            let $type = $('<select>')
                .attr('name', 'type')
                .attr('data-name', 'type')
                .addClass('form-control');

            this.typeList.forEach(type => {
                let $o = $('<option>')
                    .attr('value', type)
                    .text(this.getLanguage().translateOption(type, 'reminderTypes'));

                $type.append($o);
            });

            $type.val(item.type)
                .addClass('radius-left');

            $type.on('change', () => {
                this.updateType($type.val(), $type.closest('.reminder').index());
            });

            let $seconds = $('<select>')
                .attr('name', 'seconds')
                .attr('data-name', 'seconds')
                .addClass('form-control radius-right');

            let limitDate = this.model.get(this.dateField) ?
                this.getDateTime().toMoment(this.model.get(this.dateField)) : null;

            /** @var {Number[]} secondsList */
            let secondsList = Espo.Utils.clone(this.secondsList);

            if (!secondsList.includes(item.seconds)) {
                secondsList.push(item.seconds);
            }

            secondsList
                .filter(seconds => {
                    return seconds === item.seconds || !limitDate ||
                        this.isBefore(seconds, limitDate);
                })
                .sort((a, b) => a - b)
                .forEach(seconds => {
                    let $o = $('<option>')
                        .attr('value', seconds)
                        .text(this.stringifySeconds(seconds));

                    $seconds.append($o);
                });

            $seconds.val(item.seconds);

            $seconds.on('change', () => {
                let seconds = parseInt($seconds.val());
                let index = $seconds.closest('.reminder').index();

                this.updateSeconds(seconds, index);
            });

            let $remove = $('<button>')
                .addClass('btn')
                .addClass('btn-link')
                .css('margin-left', '5px')
                .attr('type', 'button')
                .attr('data-action', 'removeReminder')
                .html('<span class="fas fa-times"></span>');

            $item
                .append($('<div class="input-group-item">').append($type))
                .append($('<div class="input-group-item">').append($seconds))
                .append($('<div class="input-group-btn">').append($remove));

            this.$container.append($item);

            Select.init($type, {});
            Select.init($seconds, {
                sortBy: '$score',
                sortDirection: 'desc',
                /**
                 * @param {string} search
                 * @param {{value: string}} item
                 * @return {number}
                 */
                score: (search, item) => {
                    let num = parseInt(item.value);
                    let searchNum = parseInt(search);

                    if (isNaN(searchNum)) {
                        return 0;
                    }

                    let numOpposite = Number.MAX_SAFE_INTEGER - num;

                    if (searchNum === 0 && num === 0) {
                        return numOpposite;
                    }

                    if (searchNum * 60 === num) {
                        return numOpposite;
                    }

                    if (searchNum * 60 * 60 === num) {
                        return numOpposite;
                    }

                    if (searchNum * 60 * 60 * 24 === num) {
                        return numOpposite;
                    }

                    return 0;
                },
                load: (item, callback) => {
                    let num = parseInt(item);

                    if (isNaN(num) || num < 0) {
                        return;
                    }

                    if (num > 59) {
                        return;
                    }

                    let list = [];

                    let mSeconds = num * 60;

                    if (!this.isBefore(mSeconds, limitDate)) {
                        return;
                    }

                    list.push({
                        value: mSeconds.toString(),
                        text: this.stringifySeconds(mSeconds),
                    });

                    if (num <= 24) {
                        let hSeconds = num * 3600;

                        if (this.isBefore(hSeconds, limitDate)) {
                            list.push({
                                value: hSeconds.toString(),
                                text: this.stringifySeconds(hSeconds),
                            });
                        }
                    }

                    if (num <= 30) {
                        let dSeconds = num * 3600 * 24;

                        if (this.isBefore(dSeconds, limitDate)) {
                            list.push({
                                value: dSeconds.toString(),
                                text: this.stringifySeconds(dSeconds),
                            });
                        }
                    }

                    callback(list);
                }
            });
        },

        isBefore: function (seconds, limitDate) {
            return moment.utc().add(seconds, 'seconds').isBefore(limitDate);
        },

        stringifySeconds: function (totalSeconds) {
            if (!totalSeconds) {
                return this.translate('on time', 'labels', 'Meeting');
            }

            let d = totalSeconds;
            let days = Math.floor(d / 86400);
            d = d % 86400;
            let hours = Math.floor(d / 3600);
            d = d % 3600;
            let minutes = Math.floor(d / 60);
            let seconds = d % 60;

            let parts = [];

            if (days) {
                parts.push(days + '' + this.getLanguage().translate('d', 'durationUnits'));
            }

            if (hours) {
                parts.push(hours + '' + this.getLanguage().translate('h', 'durationUnits'));
            }

            if (minutes) {
                parts.push(minutes + '' + this.getLanguage().translate('m', 'durationUnits'));
            }

            if (seconds) {
                parts.push(seconds + '' + this.getLanguage().translate('s', 'durationUnits'));
            }

            return parts.join(' ') + ' ' + this.translate('before', 'labels', 'Meeting');
        },

        convertSeconds: function (seconds) {
            return seconds;
        },

        getDetailItemHtml: function (item) {
            return $('<div>')
                .append(
                    $('<span>').text(this.getLanguage().translateOption(item.type, 'reminderTypes')),
                    ' ',
                    $('<span>').text(this.stringifySeconds(item.seconds))
                )
                .get(0).outerHTML;
        },

        getValueForDisplay: function () {
            if (this.isDetailMode() || this.isListMode()) {
                let html = '';

                this.reminderList.forEach(item => {
                    html += this.getDetailItemHtml(item);
                });

                return html;
            }
        },

        fetch: function () {
            let data = {};

            data[this.name] = Espo.Utils.cloneDeep(this.reminderList);

            return data;
        },
    });
});
