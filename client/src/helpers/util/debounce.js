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

/**
 * A debounce helper.
 *
 * @since 9.1.0
 */
export default class DebounceHelper {

    /**
     * @type {boolean}
     * @private
     */
    blocked = false

    /**
     * @type {boolean}
     * @private
     */
    blockedInProcess = false

    /**
     * @type {boolean}
     * @private
     */
    calledWhenProcessBlocked = false

    /**
     * @type {number}
     * @private
     */
    interval = 500

    /**
     * @type {number}
     * @private
     */
    blockInterval = 1000

    /**
     * @type {number}
     * @private
     */
    blockedCallCount = 0

    /**
     * @type {number|null}
     * @private
     */
    blockTimeoutId = null

    /**
     * @param {{
     *     handler: function(...*),
     *     interval?: number,
     *     blockInterval?: number,
     * }} options
     * @param options
     */
    constructor(options) {
        /**
         * @private
         * @type {function(...*)}
         */
        this.handler = options.handler;

        this.interval = options.interval ?? this.interval;
        this.blockInterval = options.blockInterval ?? this.blockInterval;
    }

    /**
     * Process.
     *
     * @param {...*} [arguments]
     */
    process() {
        const handle = () => {
            if (this.blocked) {
                this.blockedCallCount ++;

                return;
            }

            if (this.blockedInProcess) {
                this.calledWhenProcessBlocked = true;

                return;
            }

            this.handler(arguments);

            this.blockedInProcess = true;

            setTimeout(() => {
                const reRun = this.calledWhenProcessBlocked;

                this.blockedInProcess = false;
                this.calledWhenProcessBlocked = false;

                if (reRun) {
                    handle();
                }
            }, this.interval);
        };

        handle();
    }

    /**
     * Block for a while.
     *
     * @since 9.2.0
     */
    block() {
        this.blocked = true;

        if (this.blockTimeoutId) {
            clearTimeout(this.blockTimeoutId);
        }

        this.blockTimeoutId = setTimeout(() => {
            this.blocked = false;
            const toProcess = this.blockedCallCount > 1;
            this.blockedCallCount = 0;

            if (toProcess) {
                this.process();
            }
        }, this.blockInterval)
    }
}
