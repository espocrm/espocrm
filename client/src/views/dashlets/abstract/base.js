/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

    noPadding = false

    actionList = [
        {
            name: 'refresh',
            label: 'Refresh',
            iconHtml: '<span class="fas fa-sync-alt"></span>',
        },
        {
            name: 'options',
            label: 'Options',
            iconHtml: '<span class="fas fa-pencil-alt"></span>',
        },
        {
            name: 'remove',
            label: 'Remove',
            iconHtml: '<span class="fas fa-times"></span>',
        }
    ]

    buttonList = []

    /**
     * Refresh.
     */
    actionRefresh() {
        this.render();
    }

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

        let options = Espo.Utils.cloneDeep(this.defaultOptions);

        for (let key in options) {
            if (typeof options[key] == 'function') {
                options[key] = options[key].call(this);
            }
        }

        let storedOptions;

        if (!this.options.readOnly) {
            storedOptions = this.getPreferences().getDashletOptions(this.id) || {};
        }
        else {
            let allOptions = this.getConfig().get('forcedDashletsOptions') ||
                this.getConfig().get('dashletsOptions') || {};

            storedOptions = allOptions[this.id] || {};
        }

        this.optionsData = _.extend(options, storedOptions);

        if (this.optionsData.autorefreshInterval) {
            let interval = this.optionsData.autorefreshInterval * 60000;

            let t;

            let process = () => {
                t = setTimeout(() => {
                    this.actionRefresh();

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
        return this.getParentView();
    }
}

export default BaseDashletView;
