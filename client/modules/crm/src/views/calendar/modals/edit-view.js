/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('crm:views/calendar/modals/edit-view', ['views/modal', 'model'], function (Dep, Model) {

    return Dep.extend({

        _template: '<div class="record-container">{{{record}}}</div>',

        buttonList: [
            {
                name: 'cancel',
                label: 'Cancel'
            }
        ],

        setup: function () {
            var id = this.options.id;

            if (id) {
                this.isNew = false;
            } else {
                this.isNew = true;
            }

            var calendarViewDataList = this.getPreferences().get('calendarViewDataList') || [];

            if (this.isNew) {
                this.buttonList.unshift({
                    name: 'save',
                    label: 'Create',
                    style: 'danger'
                });
            } else {
                this.buttonList.unshift({
                    name: 'remove',
                    label: 'Remove'
                });

                this.buttonList.unshift({
                    name: 'save',
                    label: 'Save',
                    style: 'primary'
                });
            }

            var model = new Model();
            model.name = 'CalendarView';

            var modelData = {};
            if (!this.isNew) {
                calendarViewDataList.forEach(function (item) {
                    if (id === item.id) {
                        modelData.teamsIds = item.teamIdList || [];
                        modelData.teamsNames = item.teamNames || {};
                        modelData.id = item.id;
                        modelData.name = item.name;
                        modelData.mode = item.mode;
                    }
                });
            } else {
                modelData.name = this.translate('Shared', 'labels', 'Calendar');
                var foundCount = 0;
                calendarViewDataList.forEach(function (item) {
                    if (item.name.indexOf(modelData.name) === 0) {
                        foundCount++;
                    }
                }, this);
                if (foundCount) {
                    modelData.name += ' ' + foundCount;
                }

                modelData.id = id;

                modelData.teamsIds = this.getUser().get('teamsIds') || [];
                modelData.teamsNames = this.getUser().get('teamsNames') || {};
            }

            model.set(modelData);

            this.createView('record', 'crm:views/calendar/record/edit-view', {
                el: this.options.el + ' .record-container',
                model: model
            });
        },

        actionSave: function () {
            var modelData = this.getView('record').fetch();
            this.getView('record').model.set(modelData);

            if (this.getView('record').validate()) {
                return;
            }

            this.disableButton('save');
            this.disableButton('remove');

            var calendarViewDataList = this.getPreferences().get('calendarViewDataList') || [];

            var data = {
                name: modelData.name,
                teamIdList: modelData.teamsIds,
                teamNames: modelData.teamsNames,
                mode: modelData.mode
            };

            if (this.isNew) {
                data.id = Math.random().toString(36).substr(2, 10);
                calendarViewDataList.push(data);
            } else {
                data.id = this.getView('record').model.id;
                calendarViewDataList.forEach(function (item, i) {
                    if (item.id == data.id) {
                        calendarViewDataList[i] = data;
                    }
                }, this);
            }

            Espo.Ui.notify(this.translate('saving', 'messages'));

            this.getPreferences().save({
                'calendarViewDataList': calendarViewDataList
            }, {patch: true}).then(function () {
                Espo.Ui.notify(false);
                this.trigger('after:save', data);
                this.remove();
            }.bind(this)).fail(function () {
                this.enableButton('remove');
                this.enableButton('save');
            }.bind(this));
        },

        actionRemove: function () {
            this.confirm(this.translate('confirmation', 'messages'), function () {
                this.disableButton('save');
                this.disableButton('remove');

                var id = this.options.id;

                if (!id) return;

                var newCalendarViewDataList = [];

                var calendarViewDataList = this.getPreferences().get('calendarViewDataList') || [];

                calendarViewDataList.forEach(function (item, i) {
                    if (item.id !== id) {
                        newCalendarViewDataList.push(item);
                    }
                }, this);

                Espo.Ui.notify(this.translate('pleaseWait', 'messages'));
                this.getPreferences().save({
                    'calendarViewDataList': newCalendarViewDataList
                }, {patch: true}).then(function () {
                    Espo.Ui.notify(false);
                    this.trigger('after:remove');
                    this.remove();
                }.bind(this)).fail(function () {
                    this.enableButton('remove');
                    this.enableButton('save');
                }.bind(this));
            }.bind(this));
        }
    });
});
