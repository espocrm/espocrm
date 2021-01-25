/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('crm:views/meeting/fields/date-end', 'views/fields/datetime-optional', function (Dep) {

    return Dep.extend({

        validateAfterAllowSameDay: true,

        emptyTimeInInlineEditDisabled: true,

        noneOptionIsHidden: true,

        setup: function () {
            Dep.prototype.setup.call(this);

            this.isAllDayValue = this.model.get('isAllDay');

            this.listenTo(this.model, 'change:isAllDay', function (model, value, o) {
                if (!o.ui) return;
                if (!this.isEditMode()) return;

                if (this.isAllDayValue === undefined && !value) {
                    this.isAllDayValue = value;
                    return;
                }

                this.isAllDayValue = value;

                if (value) {
                    this.$time.val(this.noneOption);
                } else {
                    var dateTime = this.model.get('dateStart');
                    if (!dateTime) {
                        dateTime = this.getDateTime().getNow(5);
                    }
                    var m = this.getDateTime().toMoment(dateTime);
                    dateTime = m.format(this.getDateTime().getDateTimeFormat());
                    var index = dateTime.indexOf(' ');
                    var time = dateTime.substr(index + 1);

                    if (this.model.get('dateEnd')) {
                        this.$time.val(time);
                    }
                }
                this.trigger('change');
                this.controlTimePartVisibility();
            }, this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.isEditMode()) {
                this.controlTimePartVisibility();
            }
        },

        controlTimePartVisibility: function () {
            if (!this.isEditMode()) return;

            if (this.model.get('isAllDay')) {
                this.$time.addClass('hidden');
                this.$el.find('.time-picker-btn').addClass('hidden');
            } else {
                this.$time.removeClass('hidden');
                this.$el.find('.time-picker-btn').removeClass('hidden');
            }
        },

    });
});
