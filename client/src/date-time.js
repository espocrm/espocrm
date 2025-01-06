/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/** @module date-time */

import moment from 'moment';

/**
 * A date-time util.
 */
class DateTime {

    constructor() {}

    /**
     * A system date format.
     *
     * @type {string}
     */
    internalDateFormat = 'YYYY-MM-DD'

    /**
     * A system date-time format.
     *
     * @type {string}
     */
    internalDateTimeFormat = 'YYYY-MM-DD HH:mm'

    /**
     * A system date-time format including seconds.
     *
     * @type {string}
     */
    internalDateTimeFullFormat = 'YYYY-MM-DD HH:mm:ss'

    /**
     * A date format for a current user.
     *
     * @type {string}
     */
    dateFormat = 'MM/DD/YYYY'

    /**
     * A time format for a current user.
     *
     * @type {string}
     */
    timeFormat = 'HH:mm'

    /**
     * A time zone for a current user.
     *
     * @type {string|null}
     */
    timeZone = null

    /**
     * A system time zone.
     *
     * @type {string}
     */
    systemTimeZone

    /**
     * A week start for a current user.
     *
     * @type {Number}
     */
    weekStart = 1

    /** @private */
    readableDateFormatMap = {
        'DD.MM.YYYY': 'DD MMM',
        'DD/MM/YYYY': 'DD MMM',
    }

    /** @private */
    readableShortDateFormatMap = {
        'DD.MM.YYYY': 'D MMM',
        'DD/MM/YYYY': 'D MMM',
    }

    /**
     * Whether a time format has a meridian (am/pm).
     *
     * @returns {boolean}
     */
    hasMeridian() {
        return (new RegExp('A', 'i')).test(this.timeFormat);
    }

    /**
     * Get a date format.
     *
     * @returns {string}
     */
    getDateFormat() {
        return this.dateFormat;
    }

    /**
     * Get a time format.
     *
     * @returns {string}
     */
    getTimeFormat() {
        return this.timeFormat;
    }

    /**
     * Get a date-time format.
     *
     * @returns {string}
     */
    getDateTimeFormat() {
        return this.dateFormat + ' ' + this.timeFormat;
    }

    /**
     * Get a readable date format.
     *
     * @returns {string}
     */
    getReadableDateFormat() {
        return this.readableDateFormatMap[this.getDateFormat()] || 'MMM DD';
    }

    /**
     * Get a readable short date format.
     *
     * @returns {string}
     */
    getReadableShortDateFormat() {
        return this.readableShortDateFormatMap[this.getDateFormat()] || 'MMM D';
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Get a readable date-time format.
     *
     * @returns {string}
     */
    getReadableDateTimeFormat() {
        return this.getReadableDateFormat() + ' ' + this.timeFormat;
    }

    /**
     * Get a readable short date-time format.
     *
     * @returns {string}
     */
    getReadableShortDateTimeFormat() {
        return this.getReadableShortDateFormat() + ' ' + this.timeFormat;
    }

    /**
     * Convert a date from a display representation to system.
     *
     * @param {string} string A date value.
     * @returns {string|-1} A system date value.
     */
    fromDisplayDate(string) {
        const m = moment(string, this.dateFormat);

        if (!m.isValid()) {
            return -1;
        }

        return m.format(this.internalDateFormat);
    }

    /**
     * Get a time-zone.
     *
     * @returns {string}
     */
    getTimeZone() {
        return this.timeZone ? this.timeZone : 'UTC';
    }

    /**
     * Convert a date from system to a display representation.
     *
     * @param {string} string A system date value.
     * @returns {string} A display date value.
     */
    toDisplayDate(string) {
        if (!string || (typeof string !== 'string')) {
            return '';
        }

        const m = moment(string, this.internalDateFormat);

        if (!m.isValid()) {
            return '';
        }

        return m.format(this.dateFormat);
    }

    /**
     * Convert a date-time from system to a display representation.
     *
     * @param {string} string A system date-time value.
     * @returns {string|-1} A display date-time value.
     */
    fromDisplay(string) {
        let m;

        if (this.timeZone) {
            m = moment.tz(string, this.getDateTimeFormat(), this.timeZone).utc();
        }
        else {
            m = moment.utc(string, this.getDateTimeFormat());
        }

        if (!m.isValid()) {
            return -1;
        }

        return m.format(this.internalDateTimeFormat) + ':00';
    }

    /**
     * Convert a date-time from system to a display representation.
     *
     * @param {string} string A system date value.
     * @returns {string} A display date-time value.
     */
    toDisplay(string) {
        if (!string) {
            return '';
        }

        return this.toMoment(string).format(this.getDateTimeFormat());
    }

    /**
     * Get a now moment.
     *
     * @returns {moment.Moment}
     */
    getNowMoment() {
        return moment().tz(this.getTimeZone())
    }

    /**
     * Convert a system-formatted date to a moment.
     *
     * @param {string} string A date value in a system representation.
     * @returns {moment.Moment}
     * @internal
     */
    toMomentDate(string) {
        return moment.tz(string, this.internalDateFormat, this.systemTimeZone);
    }

    /**
     * Convert a system-formatted date-time to a moment.
     *
     * @param {string} string A date-time value in a system representation.
     * @returns {moment.Moment}
     * @internal
     */
    toMoment(string) {
        let m = moment.utc(string, this.internalDateTimeFullFormat);

        if (this.timeZone) {
            // noinspection JSUnresolvedReference
            m = m.tz(this.timeZone);
        }

        return m;
    }

    /**
     * Convert a date-time value from ISO to a system representation.
     *
     * @param {string} string
     * @returns {string} A date-time value in a system representation.
     */
    fromIso(string) {
        if (!string) {
            return '';
        }

        const m = moment(string).utc();

        return m.format(this.internalDateTimeFormat);
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Convert a date-time value from system to an ISO representation.
     *
     * @param string A date-time value in a system representation.
     * @returns {string} An ISO date-time value.
     */
    toIso(string) {
        return this.toMoment(string).format();
    }

    /**
     * Get a today date value in a system representation.
     *
     * @returns {string}
     */
    getToday() {
        return moment().tz(this.getTimeZone()).format(this.internalDateFormat);
    }

    /**
     * Get a date-time value in a system representation, shifted from now.
     *
     * @param {Number} shift A number to shift by.
     * @param {'minutes'|'hours'|'days'|'weeks'|'months'|'years'} type A shift unit.
     * @param {Number} [multiplicity] A number of minutes a value will be aliquot to.
     * @returns {string} A date-time value in a system representation
     */
    getDateTimeShiftedFromNow(shift, type, multiplicity) {
        if (!multiplicity) {
            return moment.utc().add(shift, type).format(this.internalDateTimeFormat);
        }

        let unix = moment().unix();

        unix = unix - (unix % (multiplicity * 60));

        return moment.unix(unix).utc().add(shift, type).format(this.internalDateTimeFormat);
    }

    /**
     * Get a date value in a system representation, shifted from today.
     *
     * @param {Number} shift A number to shift by.
     * @param {'days'|'weeks'|'months'|'years'} type A shift unit.
     * @returns {string} A date value in a system representation
     */
    getDateShiftedFromToday(shift, type) {
        return moment.tz(this.getTimeZone()).add(shift, type).format(this.internalDateFormat);
    }

    /**
     * Get a now date-time value in a system representation.
     *
     * @param {Number} [multiplicity] A number of minutes a value will be aliquot to.
     * @returns {string}
     */
    getNow(multiplicity) {
        if (!multiplicity) {
            return moment.utc().format(this.internalDateTimeFormat);
        }

        let unix = moment().unix();

        unix = unix - (unix % (multiplicity * 60));

        return moment.unix(unix).utc().format(this.internalDateTimeFormat);
    }

    /**
     * Set settings and preferences.
     *
     * @param {module:models/settings} settings Settings.
     * @param {module:models/preferences} preferences Preferences.
     * @internal
     */
    setSettingsAndPreferences(settings, preferences) {
        if (settings.has('dateFormat')) {
            this.dateFormat = settings.get('dateFormat');
        }

        if (settings.has('timeFormat')) {
            this.timeFormat = settings.get('timeFormat');
        }

        if (settings.has('timeZone')) {
            this.timeZone = settings.get('timeZone') || null;

            this.systemTimeZone = this.timeZone || 'UTC';

            if (this.timeZone === 'UTC') {
                this.timeZone = null;
            }
        }

        if (settings.has('weekStart')) {
            this.weekStart = settings.get('weekStart');
        }

        preferences.on('change', model => {
            if (model.has('dateFormat') && model.get('dateFormat')) {
                this.dateFormat = model.get('dateFormat');
            }

            if (model.has('timeFormat') && model.get('timeFormat')) {
                this.timeFormat = model.get('timeFormat');
            }

            if (model.has('timeZone') && model.get('timeZone')) {

                this.timeZone = model.get('timeZone');
            }

            if (model.has('weekStart') && model.get('weekStart') !== -1) {
                this.weekStart = model.get('weekStart');
            }

            if (this.timeZone === 'UTC') {
                this.timeZone = null;
            }
        });
    }

    /**
     * Set a language.
     *
     * @param {module:language} language A language.
     * @internal
     */
    setLanguage(language) {
        moment.updateLocale('en', {
            months: language.translatePath(['Global', 'lists', 'monthNames']),
            monthsShort: language.translatePath(['Global', 'lists', 'monthNamesShort']),
            weekdays: language.translatePath(['Global', 'lists', 'dayNames']),
            weekdaysShort: language.translatePath(['Global', 'lists', 'dayNamesShort']),
            weekdaysMin: language.translatePath(['Global', 'lists', 'dayNamesMin']),
        });

        moment.locale('en');
    }
}

export default DateTime;
