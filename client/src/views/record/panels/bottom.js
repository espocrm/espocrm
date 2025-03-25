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

/** @module views/record/panels/bottom */

import View from 'view';

/**
 * @typedef {Object} module:views/record/panels/bottom~defs
 * @property [buttonList]
 * @property [actionList]
 * @property [tabNumber] For internal purposes.
 */

/**
 * A bottom panel.
 */
class BottomPanelView extends View {

    template = 'record/panels/side'

    /**
     * A field list.
     *
     * @protected
     * @type {module:views/record/panels/side~field[]}
     */
    fieldList

    /**
     * @protected
     * @type {Array<module:views/record/panels-container~action|false>}
     */
    actionList

    /**
     * @protected
     * @type {module:views/record/panels-container~button[]}
     */
    buttonList

    /**
     * @protected
     * @type {module:views/record/panels/bottom~defs|Object.<string, *>}
     */
    defs

    /**
     * A mode.
     *
     * @protected
     * @type {'list'|'detail'|'edit'}
     */
    mode = 'detail'

    /**
     * Disable.
     *
     * @protected
     */
    disabled = false

    events = {
        /** @this BottomPanelView */
        'click .action': function (e) {
            Espo.Utils.handleAction(this, e.originalEvent, e.currentTarget, {
                actionItems: [...this.buttonList, ...this.actionList],
                className: 'panel-action',
            });
        },
    }

    data() {
        return {
            scope: this.scope,
            entityType: this.entityType,
            name: this.panelName,
            hiddenFields: this.recordHelper.getHiddenFields(),
            fieldList: this.getFieldList(),
        };
    }

    init() {
        this.panelName = this.options.panelName;
        this.defs = this.options.defs || {};
        this.recordHelper = this.options.recordHelper;

        if ('disabled' in this.options) {
            this.disabled = this.options.disabled;
        }

        this.mode = this.options.mode || this.mode;

        this.readOnlyLocked = this.options.readOnlyLocked || this.readOnly;
        this.readOnly = this.readOnly || this.options.readOnly;
        this.inlineEditDisabled = this.inlineEditDisabled || this.options.inlineEditDisabled;

        this.buttonList = Espo.Utils.clone(this.defs.buttonList || this.buttonList || []);
        this.actionList = Espo.Utils.clone(this.defs.actionList || this.actionList || []);

        this.actionList.forEach(it => {
            if (it.name) {
                it.action = it.name;
            }
        });

        this.fieldList = this.options.fieldList || this.fieldList || [];

        this.recordViewObject = this.options.recordViewObject;
    }

    setup() {
        this.setupFields();

        this.fieldList = this.fieldList.map((d) => {
            let item = d;

            if (typeof item !== 'object') {
                item = {
                    name: item,
                    viewKey: item + 'Field',
                };
            }

            item = Espo.Utils.clone(item);
            item.viewKey = item.name + 'Field';
            item.label = item.label || item.name;

            if (this.recordHelper.getFieldStateParam(item.name, 'hidden') !== null) {
                item.hidden = this.recordHelper.getFieldStateParam(item.name, 'hidden');
            }
            else {
                this.recordHelper.setFieldStateParam(item.name, 'hidden', item.hidden || false);
            }

            return item;
        });

        this.fieldList = this.fieldList.filter((item) => {
            if (!item.name) {
                return;
            }

            if (!(item.name in (((this.model.defs || {}).fields) || {}))) {
                return;
            }

            return true;
        });

        this.createFields();
    }

    /**
     * Set up fields.
     *
     * @protected
     */
    setupFields() {}

    /**
     * @return {module:views/record/panels-container~button[]}
     */
    getButtonList() {
        return this.buttonList || [];
    }

    /**
     * @return {module:views/record/panels-container~action[]}
     */
    getActionList() {
        return this.actionList || [];
    }

    /**
     * Get field views.
     *
     * @return {Object.<string,module:views/fields/base>}
     */
    getFieldViews() {
        const fields = {};

        this.getFieldList().forEach(item => {
            if (this.hasView(item.viewKey)) {
                fields[item.name] = this.getView(item.viewKey);
            }
        });

        return fields;
    }

    /**
     * @deprecated Use `getFieldViews`.
     * @todo Remove in v10.0.
     */
    getFields() {
        return this.getFieldViews();
    }

    /**
     * Get a field list.
     *
     * @return {module:views/record/panels/side~field[]}
     */
    getFieldList() {
        return this.fieldList.map(item => {
            if (typeof item !== 'object') {
                return {
                    name: item
                };
            }

            return item;
        });
    }

    /**
     * @private
     */
    createFields() {
        this.getFieldList().forEach(item => {
            let view = null;
            let field;
            let readOnly = null;

            if (typeof item === 'object') {
                field = item.name;
                view = item.view;

                if ('readOnly' in item) {
                    readOnly = item.readOnly;
                }
            }
            else {
                field = item;
            }

            if (!(field in this.model.defs.fields)) {
                return;
            }

            this.createField(field, view, null, null, readOnly);
        });
    }

    /**
     * Create a field view.
     *
     * @protected
     * @param {string} field A field name.
     * @param {string|null} [viewName] A view name/path.
     * @param {Object<string,*>} [params] Field params.
     * @param {'detail'|'edit'|'list'|null} [mode='edit'] A mode.
     * @param {boolean} [readOnly] Read-only.
     * @param {Object<string,*>} [options] View options.
     */
    createField(field, viewName, params, mode, readOnly, options) {
        const type = this.model.getFieldType(field) || 'base';

        viewName = viewName ||
            this.model.getFieldParam(field, 'view') ||
            this.getFieldManager().getViewName(type);

        const o = {
            model: this.model,
            selector: '.field[data-name="' + field + '"]',
            defs: {
                name: field,
                params: params || {},
            },
            mode: mode || this.mode,
            dataObject: this.options.dataObject,
        };

        if (options) {
            for (const param in options) {
                o[param] = options[param];
            }
        }

        let readOnlyLocked = this.readOnlyLocked;

        if (this.readOnly) {
            o.readOnly = true;
        }
        else {
            if (readOnly !== null) {
                o.readOnly = readOnly;
            }
        }

        if (readOnly) {
            readOnlyLocked = true;
        }

        if (this.inlineEditDisabled) {
            o.inlineEditDisabled = true;
        }

        if (this.recordHelper.getFieldStateParam(field, 'hidden')) {
            o.disabled = true;
        }
        if (this.recordHelper.getFieldStateParam(field, 'hiddenLocked')) {
            o.disabledLocked = true;
        }

        if (this.recordHelper.getFieldStateParam(field, 'readOnly')) {
            o.readOnly = true;
        }

        if (this.recordHelper.getFieldStateParam(field, 'required') !== null) {
            o.defs.params.required = this.recordHelper.getFieldStateParam(field, 'required');
        }

        if (!readOnlyLocked && this.recordHelper.getFieldStateParam(field, 'readOnlyLocked')) {
            readOnlyLocked = true;
        }

        if (readOnlyLocked) {
            o.readOnlyLocked = readOnlyLocked;
        }

        if (this.recordHelper.hasFieldOptionList(field)) {
            o.customOptionList = this.recordHelper.getFieldOptionList(field);
        }

        if (this.recordViewObject) {
            o.validateCallback = () => this.recordViewObject.validateField(field);
        }

        const viewKey = field + 'Field';

        this.createView(viewKey, viewName, o);
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Is tab-hidden.
     *
     * @return {boolean}
     */
    isTabHidden() {
        if (this.defs.tabNumber === -1 || typeof this.defs.tabNumber === 'undefined') {
            return false;
        }

        const parentView = this.getParentView();

        if (!parentView) {
            return this.defs.tabNumber > 0;
        }

        // noinspection JSUnresolvedReference
        if (parentView && parentView.hasTabs) {
            return parentView.currentTab !== this.defs.tabNumber;
        }

        return false;
    }
}

export default BottomPanelView;
