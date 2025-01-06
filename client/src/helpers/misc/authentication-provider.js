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

/** @module module:helpers/misc/authentication-provider */

export default class {
    /**
     * @param {module:views/record/detail} view A view.
     */
    constructor(view) {
        /**
         * @private
         * @type {module:views/record/detail}
         */
        this.view = view;

        this.metadata = view.getMetadata();

        /**
         * @private
         * @type {module:model}
         */
        this.model = view.model;

        /** @var {Object.<string, Object.<string, *>>} defs */
        const defs = view.getMetadata().get(['authenticationMethods']) || {};

        /**
         * @private
         * @type {string[]}
         */
        this.methodList = Object.keys(defs).filter(item => {
            /** @var {Object.<string, *>} */
            const data = defs[item].provider || {};

            return data.isAvailable;
        });

        /** @private */
        this.authFields = {};

        /** @private */
        this.dynamicLogicDefs = {
            fields: {},
            panels: {},
        };
    }

    /**
     * @param {function(): void} callback
     */
    setupPanelsVisibility(callback) {
        this.handlePanelsVisibility(callback);

        this.view.listenTo(this.model, 'change:method', () => this.handlePanelsVisibility(callback));
    }

    /**
     * @private
     * @param {string} method
     * @param {string} param
     * @return {*}
     */
    getFromMetadata(method, param) {
        return this.metadata
            .get(['authenticationMethods', method, 'provider', param]) ||
        this.metadata
            .get(['authenticationMethods', method, 'settings', param]);
    }

    /**
     * @return {Object}
     */
    setupMethods() {
        this.methodList.forEach(method => this.setupMethod(method));

        return this.dynamicLogicDefs;
    }

    /**
     * @private
     */
    setupMethod(method) {
        /** @var {string[]} */
        let fieldList = this.getFromMetadata(method, 'fieldList') || [];

        fieldList = fieldList.filter(item => this.model.hasField(item));

        this.authFields[method] = fieldList;

        const mDynamicLogicFieldsDefs = (this.getFromMetadata(method, 'dynamicLogic') || {}).fields || {};

        for (const f in mDynamicLogicFieldsDefs) {
            if (!fieldList.includes(f)) {
                continue;
            }

            const defs = this.modifyDynamicLogic(mDynamicLogicFieldsDefs[f]);

            this.dynamicLogicDefs.fields[f] = Espo.Utils.cloneDeep(defs);
        }
    }

    /**
     * @private
     */
    modifyDynamicLogic(defs) {
        defs = Espo.Utils.clone(defs);

        if (Array.isArray(defs)) {
            return defs.map(item => this.modifyDynamicLogic(item));
        }

        if (typeof defs === 'object') {
            const o = {};

            for (const property in defs) {
                let value = defs[property];

                if (property === 'attribute' && value === 'authenticationMethod') {
                    value = 'method';
                }

                o[property] = this.modifyDynamicLogic(value);
            }

            return o;
        }

        return defs;
    }

    modifyDetailLayout(layout) {
        this.methodList.forEach(method => {
            let mLayout = this.getFromMetadata(method, 'layout');

            if (!mLayout) {
                return;
            }

            mLayout = Espo.Utils.cloneDeep(mLayout);
            mLayout.name = method;

            this.prepareLayout(mLayout, method);

            layout.push(mLayout);
        });
    }

    prepareLayout(layout, method) {
        layout.rows.forEach(row => {
            row
                .filter(item => !item.noLabel && !item.labelText && item.name)
                .forEach(item => {
                    if (item === null) {
                        return;
                    }

                    const labelText = this.view.translate(item.name, 'fields', 'Settings');

                    item.options = item.options || {};

                    if (labelText && labelText.toLowerCase().indexOf(method.toLowerCase() + ' ') === 0) {
                        item.labelText = labelText.substring(method.length + 1);
                    }

                    item.options.tooltipText = this.view.translate(item.name, 'tooltips', 'Settings');
                });
        });

        layout.rows = layout.rows.map(row => {
            row = row.map(cell => {
                if (
                    cell &&
                    cell.name &&
                    !this.model.hasField(cell.name)
                ) {
                    return false;
                }

                return cell;
            })

            return row;
        });
    }

    /**
     * @private
     * @param {function(): void} callback
     */
    handlePanelsVisibility(callback) {
        const authenticationMethod = this.model.get('method');

        this.methodList.forEach(method => {
            const fieldList = (this.authFields[method] || []);

            if (method !== authenticationMethod) {
                this.view.hidePanel(method);

                fieldList.forEach(field => {
                    this.view.hideField(field);
                });

                return;
            }

            this.view.showPanel(method);

            fieldList.forEach(field => this.view.showField(field));

            callback();
        });
    }
}
