/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

/** @module search-manager */

/**
 * A where item. Sent to the backend.
 */
import Collection, {WhereItem} from 'collection';

/**
 * Search data.
 */
interface Data {
    presetName?: string;
    textFilter?: string;
    primary?: string | null;
    bool?: Record<string, boolean>;
}

interface InternalData extends Data {
    advanced?: Record<string, AdvancedFilter>;
}

/**
 * An advanced filter
 */
export interface AdvancedFilter {
    type: string;
    attribute?: string;
    value?: WhereItem[] | string | number | boolean | string[] | null;
    data?: Record<string, unknown>;
}

interface Options {
    storageKey?: string;
    defaultData?: Data;
    emptyOnReset?: boolean;
}

import {inject} from 'di';
import DateTime from 'date-time';
import Storage from 'storage';

/**
 * A search manager.
 */
class SearchManager {

    @inject(DateTime)
    private dateTime: DateTime

    @inject(Storage)
    private storage: Storage

    private timeZone: string | null = null
    private readonly defaultData: InternalData
    private readonly scope: string | null
    private readonly storageKey: string | null
    private readonly useStorage: boolean
    private readonly emptyOnReset: boolean
    private readonly emptyData: InternalData
    private data: InternalData

    /**
     * @param collection A collection.
     * @param [options] Options. As of 9.1.
     */
    constructor(collection: Collection, options: Options = {}) {
        this.scope = collection.entityType ?? null;
        this.storageKey = options.storageKey ?? null;
        this.useStorage = !!this.storageKey;
        this.emptyOnReset = options.emptyOnReset ?? false;

        this.emptyData = {
            textFilter: '',
            bool: {},
            advanced: {},
            primary: null,
        };

        let defaultData = options.defaultData;

        if (!defaultData && arguments[4]) {
            // For bc.
            defaultData = arguments[4];
        }

        if (defaultData) {
            this.defaultData = defaultData;

            for (const key in this.emptyData) {
                if (!(key in defaultData)) {
                    (defaultData as any)[key] = Espo.Utils.clone((this.emptyData as any)[key]);
                }
            }
        }

        this.data = Espo.Utils.clone(defaultData) ?? this.emptyData;

        this.sanitizeData();
    }

    private sanitizeData() {
        if (!('advanced' in this.data)) {
            this.data.advanced = {};
        }

        if (!('bool' in this.data)) {
            this.data.bool = {};
        }

        if (!('textFilter' in this.data)) {
            this.data.textFilter = '';
        }
    }

    /**
     * Get a where clause. The where clause to be sent to the backend.
     */
    getWhere(): WhereItem[] {
        const where = [];

        if (this.data.textFilter && this.data.textFilter !== '') {
            where.push({
                type: 'textFilter',
                value: this.data.textFilter
            });
        }

        if (this.data.bool) {
            const o = {
                type: 'bool',
                value: [] as string[],
            };

            for (const name in this.data.bool) {
                if (this.data.bool[name]) {
                    o.value.push(name);
                }
            }

            if (o.value.length) {
                where.push(o);
            }
        }

        if (this.data.primary) {
            const o = {
                type: 'primary',
                value: this.data.primary,
            };

            if (o.value.length) {
                where.push(o);
            }
        }

        if (this.data.advanced) {
            for (const name in this.data.advanced) {
                const defs = this.data.advanced[name];

                if (!defs) {
                    continue;
                }

                const part = this.getWherePart(name, defs);

                if (part === null) {
                    continue;
                }

                where.push(part);
            }
        }

        return where;
    }

    private getWherePart(name: string, defs: WhereItem): AdvancedFilter | null {
        let attribute = name;

        if (typeof defs !== 'object') {
            console.error('Bad where clause');

            return null;
        }

        if ('where' in defs) {
            return defs.where as AdvancedFilter;
        }

        const type = defs.type;
        let value: unknown;

        if (type === 'or' || type === 'and') {
            const items = [];

            const value = (defs.value || {}) as Record<string, WhereItem>;

            for (const field in value) {
                const part = this.getWherePart(field, value[field]);

                if (part === null) {
                    continue;
                }

                items.push(part);
            }

            return {
                type: type,
                value: items,
            };
        }

        if ('field' in defs) { // for backward compatibility
            attribute = defs.field as string;
        }

        if ('attribute' in defs) {
            attribute = defs.attribute as string;
        }

        if (defs.dateTime || (defs as any).date) {
            const timeZone = this.timeZone !== undefined ?
                this.timeZone :
                this.dateTime.getTimeZone();

            const data = {
                type: type,
                attribute: attribute,
                value: defs.value,
            } as WhereItem;

            if (defs.dateTime) {
                data.dateTime = true;
            }

            // @todo Revise.
            // @ts-ignore
            if (defs.date) {
                // @ts-ignore
                data.date = true;
            }

            if (timeZone) {
                data.timeZone = timeZone;
            }

            return data;
        }

        value = defs.value;

        return {
            type: type,
            attribute: attribute,
            value: value as WhereItem['value'],
        };
    }

    /**
     * Load stored data.
     */
    loadStored(): this {
        this.data = this.getFromStorageIfEnabled() ||
            Espo.Utils.clone(this.defaultData) ||
            Espo.Utils.clone(this.emptyData);

        this.sanitizeData();

        return this;
    }

    private getFromStorageIfEnabled(): Data | null {
        if (!this.useStorage || !this.scope) {
            return null;
        }

        return this.storage.get(`${this.storageKey}Search`, this.scope);
    }

    /**
     * Get data.
     */
    get(): Data {
        return this.data;
    }

    /**
     * Set advanced filters.
     *
     * @param advanced Advanced filters.
     *   Pairs of field => advancedFilter.
     */
    setAdvanced(advanced: Record<string, AdvancedFilter>) {
        this.data = Espo.Utils.clone(this.data);

        this.data.advanced = advanced;
    }

    /**
     * Set bool filters.
     *
     * @param bool Bool filters.
     */
    setBool(bool: Record<string, boolean> | string[]) {
        if (Array.isArray(bool)) {
            const data = {} as Record<string, boolean>;

            bool.forEach(it => data[it] = true);

            bool = data;
        }

        this.data = Espo.Utils.clone(this.data);

        this.data.bool = bool;
    }

    /**
     * Set a primary filter.
     *
     * primary A filter.
     */
    setPrimary(primary: string) {
        this.data = Espo.Utils.clone(this.data);

        this.data.primary = primary;
    }

    /**
     * Set data.
     *
     * @param data Data.
     */
    set(data: Data) {
        this.data = data;

        if (this.useStorage && this.scope) {
            data = Espo.Utils.clone(data);
            delete data['textFilter'];

            this.storage.set(this.storageKey + 'Search', this.scope, data);
        }
    }

    clearPreset() {
        delete this.data.presetName;
    }

    /**
     * Empty data.
     */
    empty() {
        this.data = Espo.Utils.clone(this.emptyData);

        if (this.useStorage && this.scope) {
            this.storage.clear(this.storageKey + 'Search', this.scope);
        }
    }

    /**
     * Reset.
     */
    reset() {
        if (this.emptyOnReset) {
            this.empty();

            return;
        }

        this.data = Espo.Utils.clone(this.defaultData) || Espo.Utils.clone(this.emptyData);

        if (this.useStorage && this.scope) {
            this.storage.clear(this.storageKey + 'Search', this.scope);
        }
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Set a time zone. Null will not add a time zone.
     *
     * @internal Is used. Do not remove.
     */
    setTimeZone(timeZone: string | null) {
        this.timeZone = timeZone;
    }
}

export default SearchManager;
