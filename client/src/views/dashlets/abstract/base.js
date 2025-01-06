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

/** @module views/dashlets/abstract/base */

import View from 'view';

/**
 * A base dashlet view. All dashlets should extend it.
 */
class BaseDashletView extends View {

    /** @type {Object.<string, *>|null}*/
    optionsData = null

    optionsFields = {
        title: {
            type: 'varchar',
            required: true,
        },
        autorefreshInterval: {
            type: 'enumFloat',
            options: [0, 0.5, 1, 2, 5, 10],
        },
    }

    disabledForReadOnlyActionList = ['options', 'remove']
    disabledForLockedActionList = ['remove']

    /**
     * @type {boolean}
     */
    noPadding = false

    /**
     * A button. Handled by an `action{Name}` method or a click handler.
     *
     * @typedef module:views/dashlets/abstract/base~button
     *
     * @property {string} name A name.
     * @property {string} [label] A label.
     * @property {string} [html] An HTML.
     * @property {string} [text] A text.
     * @property {string} [title] A title (not translatable).
     * @property {function()} [onClick] A click handler.
     */

    /**
     * A dropdown action. Handled by an `action{Name}` method or a click handler.
     *
     * @typedef module:views/dashlets/abstract/base~action
     *
     * @property {string} name A name.
     * @property {string} [label] A label.
     * @property {string} [html] An HTML.
     * @property {string} [text] A text.
     * @property {string} [title] A title (not translatable).
     * @property {string} [iconHtml] An icon HTML.
     * @property {string} [url] A link URL.
     * @property {function()} [onClick] A click handler.
     * @property {number} [groupIndex] A group index.
     */

    /**
     * Buttons.
     *
     * @type {Array<module:views/dashlets/abstract/base~button>}
     */
    buttonList = []

    /**
     * Dropdown actions.
     *
     * @type {Array<module:views/dashlets/abstract/base~action>}
     */
    actionList = [
        {
            name: 'refresh',
            label: 'Refresh',
            iconHtml: '<span class="fas fa-sync-alt"></span>',
            groupIndex: 10000,
        },
        {
            name: 'options',
            label: 'Options',
            iconHtml: '<span class="fas fa-pencil-alt"></span>',
            groupIndex: 10000,
        },
        {
            name: 'remove',
            label: 'Remove',
            iconHtml: '<span class="fas fa-times"></span>',
            groupIndex: 10000,
        },
    ]

    /**
     * Refresh.
     */
    actionRefresh() {
        this.render();
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Show options.
     */
    actionOptions() {}

    init() {
        this.name = this.options.name || this.name;
        this.id = this.options.id;

        this.defaultOptions = this.getMetadata().get(['dashlets', this.name, 'options', 'defaults']) ||
            this.defaultOptions || {};

        this.defaultOptions = {
            title: this.getLanguage().translate(this.name, 'dashlets'),
            ...this.defaultOptions
        };

        this.defaultOptions = Espo.Utils.clone(this.defaultOptions);

        this.optionsFields = this.getMetadata().get(['dashlets', this.name, 'options', 'fields']) ||
            this.optionsFields || {};

        this.optionsFields = Espo.Utils.clone(this.optionsFields);

        this.setupDefaultOptions();

        const options = Espo.Utils.cloneDeep(this.defaultOptions);

        for (const key in options) {
            if (typeof options[key] == 'function') {
                options[key] = options[key].call(this);
            }
        }

        let storedOptions;

        if (!this.options.readOnly) {
            storedOptions = this.getPreferences().getDashletOptions(this.id) || {};
        }
        else {
            const allOptions = this.getConfig().get('forcedDashletsOptions') ||
                this.getConfig().get('dashletsOptions') || {};

            storedOptions = allOptions[this.id] || {};
        }

        this.optionsData = _.extend(options, storedOptions);

        if (this.optionsData.autorefreshInterval) {
            const interval = this.optionsData.autorefreshInterval * 60000;

            let t;

            const process = () => {
                t = setTimeout(() => {
                    this.autoRefresh();

                    process();
                }, interval);
            };

            process();

            this.once('remove', () => {
                clearTimeout(t);
            });
        }

        this.actionList = Espo.Utils.clone(this.actionList);
        this.buttonList = Espo.Utils.clone(this.buttonList);

        if (this.options.readOnly) {
            this.actionList = this.actionList.filter(item => {
                if (~this.disabledForReadOnlyActionList.indexOf(item.name)) {
                    return false;
                }

                return true;
            })
        }

        if (this.options.locked) {
            this.actionList = this.actionList
                .filter(item => !this.disabledForLockedActionList.includes(item.name));
        }

        this.setupActionList();
        this.setupButtonList();
    }

    /**
     * Called on auto-refresh.
     *
     * @protected
     */
    autoRefresh() {
        this.actionRefresh();
    }

    /**
     * Set up default options.
     */
    setupDefaultOptions() {}

    /**
     * Set up actions.
     */
    setupActionList() {}

    /**
     * Set up buttons.
     */
    setupButtonList() {}

    /**
     * Has an option.
     *
     * @param {string} key
     * @return {boolean}
     */
    hasOption(key) {
        return key in this.optionsData;
    }

    /**
     * Get an option value.
     *
     * @param {string} key
     * @return {*}
     */
    getOption(key) {
        return this.optionsData[key];
    }

    /**
     * Get a title.
     * @return {string|null}
     */
    getTitle() {
        let title = this.getOption('title');

        if (!title) {
            title = null;
        }

        return title;
    }

    /**
     * @return {module:views/dashlet}
     */
    getContainerView() {
        return /** @type module:views/dashlet */this.getParentView();
    }

    /**
     * @internal
     * @param {MouseEvent} event
     * @param {HTMLElement} element
     */
    handleAction(event, element) {
        Espo.Utils.handleAction(this, event, element, {
            actionItems: [...this.buttonList, ...this.actionList],
            className: 'dashlet-action',
        });
    }

    /**
     * @internal
     * @return {Array<module:views/dashlets/abstract/base~action|false>}
     */
    getActionItemDataList() {
        /** @type {Array<module:views/dashlets/abstract/base~action[]>} */
        const groups = [];

        this.actionList.forEach(item => {
            // For bc.
            if (item === false) {
                return;
            }

            const index = (item.groupIndex === undefined ? 9999 : item.groupIndex) + 100;

            if (groups[index] === undefined) {
                groups[index] = [];
            }

            groups[index].push(item);
        });

        const itemList = [];

        groups.forEach(list => {
            list.forEach(it => itemList.push(it));

            itemList.push(false);
        });

        if (itemList.at(itemList.length - 1) === false) {
            itemList.pop();
        }

        return itemList;
    }

    /**
     * @return {string|null}
     * @since 9.0.0
     */
    getColor() {
        return null;
    }

    afterAdding() {}
}

export default BaseDashletView;
