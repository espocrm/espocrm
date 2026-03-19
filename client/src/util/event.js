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

/**
 * @internal
 * Listen to model attribute change.
 *
 * @param {{
 *     owner: import('view').default | import('model').default | import('collection').default,
 *     target: import('model').default,
 *     attributes?: string[],
 *     once?: boolean,
 *     callback: function({
 *         ui: boolean|null,
 *         action: string|'ui'|'save'|'fetch'|'cancel-edit'|null,
 *     }),
 * }} params
 * @return {{stop: function()}}
 * @since 9.4.0
 */
export function onModelChange(params) {
    const owner = params.owner;
    const target = params.target;
    const callback = params.callback;

    let event = 'change';

    if (params.attributes) {
        event = params.attributes
            .map(it => `change:${it}`)
            .join(' ');
    }

    /**
     * @param {Record} o
     */
    function callCallback(o) {
        callback({
            ui: o.ui ?? null,
            action: o.action ?? null,
        });
    }

    const wrappedCallback = params.attributes ?
        (m, v, o) => callCallback(o) :
        (m, o) => callCallback(o);

    function stop() {
        owner.stopListening(target, event, wrappedCallback);
    }

    const output = {stop};

    if (params.once) {
        owner.listenToOnce(target, event, wrappedCallback);

        return output;
    }

    owner.listenTo(target, event, wrappedCallback);

    return output;
}

/**
 * @internal
 * Listen to sync.
 *
 * @param {{
 *     owner: import('view').default | import('model').default | import('collection').default,
 *     target: import('model').default | import('collection').default,
 *     once?: boolean,
 *     callback: function({
 *         action: 'fetch'|'save'|'destroy'|null,
 *         response: *,
 *     }),
 * }} params
 * @return {{stop: function()}}
 * @since 9.4.0
 */
export function onSync(params) {
    const owner = params.owner;
    const target = params.target;
    const callback = params.callback;

    const event = 'sync';

    /**
     * @param {Record} o
     * @param {*} response
     */
    function wrappedCallback(o, response) {
        callback({
            action: o.action ?? null,
            response: response,
        });
    }

    function stop() {
        owner.stopListening(target, event, wrappedCallback);
    }

    const output = {stop};

    if (params.once) {
        owner.listenToOnce(target, event, wrappedCallback);

        return output;
    }

    owner.listenTo(target, event, wrappedCallback);

    return output;
}
