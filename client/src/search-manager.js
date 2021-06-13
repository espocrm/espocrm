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

define('search-manager', [], function () {

    let SearchManager = function (collection, type, storage, dateTime, defaultData, emptyOnReset) {
        this.collection = collection;
        this.scope = collection.name;
        this.storage = storage;
        this.type = type || 'list';
        this.dateTime = dateTime;
        this.emptyOnReset = emptyOnReset;

        this.emptyData = {
            textFilter: '',
            bool: {},
            advanced: {},
            primary: null,
        };

        if (defaultData) {
            this.defaultData = defaultData;

            for (let p in this.emptyData) {
                if (!(p in defaultData)) {
                    defaultData[p] = Espo.Utils.clone(this.emptyData[p]);
                }
            }
        }

        this.data = Espo.Utils.clone(defaultData) || this.emptyData;

        this.sanitizeData();
    };

    _.extend(SearchManager.prototype, {

        data: null,

        sanitizeData: function () {
            if (!('advanced' in this.data)) {
                this.data.advanced = {};
            }

            if (!('bool' in this.data)) {
                this.data.bool = {};
            }

            if (!('textFilter' in this.data)) {
                this.data.textFilter = '';
            }
        },

        getWhere: function () {
            let where = [];

            if (this.data.textFilter && this.data.textFilter !== '') {
                where.push({
                    type: 'textFilter',
                    value: this.data.textFilter
                });
            }

            if (this.data.bool) {
                let o = {
                    type: 'bool',
                    value: [],
                };

                for (let name in this.data.bool) {
                    if (this.data.bool[name]) {
                        o.value.push(name);
                    }
                }

                if (o.value.length) {
                    where.push(o);
                }
            }

            if (this.data.primary) {
                let o = {
                    type: 'primary',
                    value: this.data.primary,
                };

                if (o.value.length) {
                    where.push(o);
                }
            }

            if (this.data.advanced) {
                for (let name in this.data.advanced) {
                    let defs = this.data.advanced[name];

                    if (!defs) {
                        continue;
                    }

                    let part = this.getWherePart(name, defs);

                    where.push(part);
                }
            }

            return where;
        },

        getWherePart: function (name, defs) {
            var attribute = name;

            if ('where' in defs) {
                return defs.where;
            }

            let type = defs.type;

            if (type === 'or' || type === 'and') {
                let a = [];

                var value = defs.value || {};

                for (let n in value) {
                    a.push(this.getWherePart(n, value[n]));
                }

                return {
                    type: type,
                    value: a
                };
            }

            if ('field' in defs) { // for backward compatibility
                attribute = defs.field;
            }

            if ('attribute' in defs) {
                attribute = defs.attribute;
            }

            if (defs.dateTime) {
                return {
                    type: type,
                    attribute: attribute,
                    value: defs.value,
                    dateTime: true,
                    timeZone: this.dateTime.timeZone || 'UTC',
                };
            }

            value = defs.value;

            return {
                type: type,
                attribute: attribute,
                value: value
            };
        },

        loadStored: function () {
            this.data =
                this.storage.get(this.type + 'Search', this.scope) ||
                Espo.Utils.clone(this.defaultData) ||
                Espo.Utils.clone(this.emptyData);

            this.sanitizeData();

            return this;
        },

        get: function () {
            return this.data;
        },

        setAdvanced: function (advanced) {
            this.data = Espo.Utils.clone(this.data);

            this.data.advanced = advanced;
        },

        setBool: function (bool) {
            this.data = Espo.Utils.clone(this.data);

            this.data.bool = bool;
        },

        setPrimary: function (primary) {
            this.data = Espo.Utils.clone(this.data);

            this.data.primary = primary;
        },

        set: function (data) {
            this.data = data;

            if (this.storage) {
                this.storage.set(this.type + 'Search', this.scope, data);
            }
        },

        empty: function () {
            this.data = Espo.Utils.clone(this.emptyData);

            if (this.storage) {
                this.storage.clear(this.type + 'Search', this.scope);
            }
        },

        reset: function () {
            if (this.emptyOnReset) {
                this.empty();

                return;
            }

            this.data = Espo.Utils.clone(this.defaultData) || Espo.Utils.clone(this.emptyData);

            if (this.storage) {
                this.storage.clear(this.type + 'Search', this.scope);
            }
        },

        getDateTimeWhere: function (type, field, value) {
            var where = {
                field: field
            };

            if (!value && ~['on', 'before', 'after'].indexOf(type)) {
                return null;
            }

            let start, from, to;

            switch (type) {
                case 'today':
                    where.type = 'between';

                    start = this.dateTime.getNowMoment().startOf('day').utc();

                    from = start.format(this.dateTime.internalDateTimeFormat);
                    to = start.add(1, 'days').format(this.dateTime.internalDateTimeFormat);

                    where.value = [from, to];

                    break;

                case 'past':
                    where.type = 'before';

                    where.value = this.dateTime.getNowMoment().utc().format(this.dateTime.internalDateTimeFormat);

                    break;

                case 'future':
                    where.type = 'after';

                    where.value = this.dateTime.getNowMoment().utc().format(this.dateTime.internalDateTimeFormat);

                    break;

                case 'on':
                    where.type = 'between';

                    start = moment(value, this.dateTime.internalDateFormat, this.timeZone).utc();

                    from = start.format(this.dateTime.internalDateTimeFormat);
                    to = start.add(1, 'days').format(this.dateTime.internalDateTimeFormat);

                    where.value = [from, to];

                    break;

                case 'before':
                    where.type = 'before';
                    where.value =
                        moment(
                            value,
                            this.dateTime.internalDateFormat,
                            this.timeZone
                        )
                        .utc()
                        .format(this.dateTime.internalDateTimeFormat );

                    break;

                case 'after':
                    where.type = 'after';
                    where.value =
                        moment(
                            value,
                            this.dateTime.internalDateFormat,
                            this.timeZone
                        )
                        .utc()
                        .format(this.dateTime.internalDateTimeFormat);

                    break;

                case 'between':
                    where.type = 'between';

                    if (value[0] && value[1]) {
                        let from =
                            moment(
                                value[0],
                                this.dateTime.internalDateFormat,
                                this.timeZone
                            )
                            .utc()
                            .format(this.dateTime.internalDateTimeFormat);

                        let to =
                            moment(
                                value[1],
                                this.dateTime.internalDateFormat,
                                this.timeZone
                            )
                            .utc()
                            .format(this.dateTime.internalDateTimeFormat);

                        where.value = [from, to];
                    }

                    break;

                default:
                    where.type = type;
            }

            return where;
        },
    });

    return SearchManager;
});
