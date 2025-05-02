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

import View from 'view';
import Model from 'model';
import EnumFieldView from 'views/fields/enum';
import ViewRecordHelper from 'view-record-helper';

class RoleRecordTableView extends View {

    template = 'role/table'

    /**
     * @type {string[]}
     */
    scopeList

    type = 'acl'

    /**
     * @type {'detail'|'edit'}
     */
    mode = 'detail'

    lowestLevelByDefault = true
    collaborators = true

    actionList = ['create', 'read', 'edit', 'delete', 'stream']
    accessList = ['not-set', 'enabled', 'disabled']
    fieldLevelList = ['yes', 'no']
    fieldActionList = ['read', 'edit']
    levelList = ['yes', 'all', 'team', 'own', 'no']
    booleanLevelList = ['yes', 'no']
    booleanActionList = ['create']

    levelListMap = {
        'recordAllTeamOwnNo': ['all', 'team', 'own', 'no'],
        'recordAllTeamNo': ['all', 'team', 'no'],
        'recordAllOwnNo': ['all', 'own', 'no'],
        'recordAllNo': ['all', 'no'],
        'record': ['all', 'team', 'own', 'no'],
    }

    defaultLevels = {
        delete: 'no',
    }

    styleMap = {
        yes: 'success',
        all: 'success',
        account: 'info',
        contact: 'info',
        team: 'info',
        own: 'warning',
        no: 'danger',
        enabled: 'success',
        disabled: 'danger',
        'not-set': 'muted',
    }

    /**
     * @private
     * @type {Object.<Record>}
     */
    scopeLevelMemory

    /**
     * @type {import('model').default}
     */
    formModel

    /**
     * @private
     * @type {ViewRecordHelper}
     */
    formRecordHelper

    /**
     * @private
     * @type {Object.<string, EnumFieldView>}
     */
    enumViews

    /**
     * @private
     * @type {{
     *     data: Object.<string, Record.<string, string>|false>,
     *     fieldData: Object.<string, Object.<string, Record.<string, string>|false>>,
     * }}
     */
    acl

    /**
     * @private
     * @type {
     *     {
     *         name: string,
     *         list: {
     *             name: string,
     *             list: {
     *                 action: 'read'|'edit',
     *                 value: 'yes'|'no',
     *                 name: string,
     *            }[],
     *          }[],
     *     }[]
     * }
     */
    fieldTableDataList

    data() {
        const data = {};

        data.styleMap = this.styleMap;
        data.editMode = this.mode === 'edit';
        data.actionList = this.actionList;
        data.accessList = this.accessList;
        data.fieldActionList = this.fieldActionList;
        data.fieldLevelList = this.fieldLevelList;

        data.tableDataList = this.getTableDataList();
        data.fieldTableDataList = this.fieldTableDataList;

        let hasFieldLevelData = false;

        this.fieldTableDataList.forEach((d) => {
            if (d.list.length) {
                hasFieldLevelData = true;
            }
        });

        data.hasFieldLevelData = hasFieldLevelData;

        data.hiddenFields = this.formRecordHelper.getHiddenFields();

        return data;
    }

    events = {
        /** @this FieldManagerListView */
        'keyup input[data-name="quick-search"]': function (e) {
            this.processQuickSearch(e.currentTarget.value);
        },
        /** @this RoleRecordTableView */
        'click .action[data-action="addField"]': function (e) {
            const scope = $(e.currentTarget).data().scope;

            this.showAddFieldModal(scope);
        },
        /** @this RoleRecordTableView */
        'click .action[data-action="removeField"]': function (e) {
            const scope = $(e.currentTarget).data().scope;
            const field = $(e.currentTarget).data().field;

            this.removeField(scope, field);
        },
    }

    /**
     * @private
     * @return {({
     *     list: {
     *         level: string|false,
     *         name: string,
     *         action: string,
     *         levelList: string[]|null,
     *     }[],
     *     name: string,
     *     type: 'boolean'|'record',
     *     access: 'not-set'|'enabled'|'disabled',
     * }|false)[]}
     */
    getTableDataList() {
        const aclData = this.acl.data;
        const aclDataList = [];

        let currentModule = null

        this.scopeList.forEach(scope => {
            const module = this.getMetadata().get(`scopes.${scope}.module`);

            if (currentModule !== module) {
                currentModule = module;

                aclDataList.push(false);
            }

            let access = 'not-set';

            if (this.final) {
                access = 'enabled';
            }

            if (scope in aclData) {
                access = aclData[scope] === false ? 'disabled' : 'enabled';
            }

            const list = [];
            const type = this.aclTypeMap[scope];

            if (this.aclTypeMap[scope] !== 'boolean') {
                this.actionList.forEach(action => {
                    /** @type {string[]} */
                    const allowedActionList = this.getMetadata().get(`scopes.${scope}.${this.type}ActionList`);

                    if (allowedActionList && !allowedActionList.includes(action)) {
                        list.push({
                            action: action,
                            levelList: null,
                            level: null,
                        });

                        return;
                    }

                    if (action === 'stream' && !this.getMetadata().get(`scopes.${scope}.stream`)) {
                        list.push({
                            action: 'stream',
                            levelList: null,
                            level: null,
                        });

                        return;
                    }

                    let level = null;

                    const levelList = this.getLevelList(scope, action);

                    if (scope in aclData) {
                        if (access === 'enabled') {
                            if (aclData[scope] !== true) {
                                if (action in aclData[scope]) {
                                    level = aclData[scope][action];
                                }

                                if (level === null) {
                                    level = levelList[levelList.length - 1];
                                }
                            }
                        } else {
                            level = 'no';
                        }
                    }

                    if (level && !levelList.includes(level)) {
                        levelList.push(level);

                        levelList.sort((a, b) => {
                            return this.levelList.findIndex(it => it === a) -
                                this.levelList.findIndex(it => it === b);
                        });
                    }

                    list.push({
                        level: level,
                        name: `${scope}-${action}`,
                        action: action,
                        levelList: levelList,
                    });
                });
            }

            aclDataList.push({
                list: list,
                access: access,
                name: scope,
                type: type,
            });
        });

        return aclDataList;
    }

    /**
     * @private
     * @param {string} scope
     * @param {'create'|'read'|'edit'|'delete'|'stream'} action
     * @return {string[]}
     */
    getLevelList(scope, action) {
        if (this.booleanActionList.includes(action)) {
            return this.booleanLevelList;
        }

        const specifiedLevelList =
            this.getMetadata().get(`scopes.${scope}.${this.type}ActionLevelListMap.${action}`) ||
            this.getMetadata().get(`scopes.${scope}.${this.type}LevelList`);

        if (specifiedLevelList) {
            return specifiedLevelList;
        }

        const type = this.aclTypeMap[scope];

        return this.levelListMap[type] || [];
    }

    setup() {
        this.mode = this.options.mode || 'detail';
        this.final = this.options.final || false;

        this.scopeLevelMemory = {};

        this.setupData();
        this.setupFormModel();

        this.listenTo(this.model, 'change:data change:fieldData', async () => {
            this.setupData();
            await this.setupFormModel();

            if (this.isRendered()) {
                await this.reRenderPreserveSearch();
            }
        });

        this.listenTo(this.model, 'sync', async () => {
            this.setupData();
            await this.setupFormModel();

            if (this.isRendered()) {
                await this.reRenderPreserveSearch();
            }
        });

        this.template = 'role/table';

        if (this.mode === 'edit') {
            this.template = 'role/table-edit';
        }

        this.once('remove', () => {
            $(window).off('scroll.scope-' + this.cid);
            $(window).off('resize.scope-' + this.cid);
            $(window).off('scroll.field-' + this.cid);
            $(window).off('resize.field-' + this.cid);
        });
    }

    /**
     * @private
     */
    async setupFormModel() {
        const defs = {fields: {}};

        this.formModel = new Model({}, {defs: defs});
        this.formRecordHelper = new ViewRecordHelper();

        this.enumViews = {};

        const promises = [];

        this.getTableDataList().forEach(scopeItem => {
            if (!scopeItem) {
                return;
            }

            const scope = scopeItem.name;

            defs.fields[scope] = {
                type: 'enum',
                options: [
                    'not-set',
                    'enabled',
                    'disabled',
                ],
                translation: 'Role.options.accessList',
                style: this.styleMap,
            };

            this.formModel.set(scope, scopeItem.access, {silent: true});

            const view = new CustomEnumFieldView({
                name: scope,
                model: this.formModel,
                mode: this.mode,
                inlineEditDisabled: true,
                recordHelper: this.formRecordHelper,
            });

            promises.push(
                this.assignView(scope, view, `td[data-name="${scope}"]`)
            );

            if (!scopeItem.list) {
                return;
            }

            this.listenTo(this.formModel, `change:${scope}`, () => this.onSelectAccess(scope));

            scopeItem.list.forEach(actionItem => {
                if (!actionItem.levelList) {
                    return;
                }

                const name = actionItem.name;

                defs.fields[name] = {
                    type: 'enum',
                    options: actionItem.levelList,
                    translation: 'Role.options.levelList',
                    style: this.styleMap,
                };

                this.formModel.set(name, actionItem.level, {silent: true});

                const view = new CustomEnumFieldView({
                    name: name,
                    model: this.formModel,
                    mode: this.mode,
                    inlineEditDisabled: true,
                    recordHelper: this.formRecordHelper,
                });

                this.enumViews[name] = view;

                promises.push(
                    this.assignView(name, view, `div[data-name="${name}"]`)
                );

                this.formRecordHelper.setFieldStateParam(name, 'hidden', scopeItem.access !== 'enabled');

                if (actionItem.action === 'read') {
                    this.listenTo(this.formModel, `change:${scope}-read`, (m, value) => {
                        ['edit', 'delete', 'stream'].forEach(action => this.controlSelect(scope, action, value));
                    });
                }

                if (actionItem.action === 'edit') {
                    this.listenTo(this.formModel, `change:${scope}-edit`, (m, value) => {
                        this.controlSelect(scope, 'delete', value);
                    });
                }
            });

            const readLevel = this.formModel.attributes[`${scope}-read`];
            const editLevel = this.formModel.attributes[`${scope}-edit`];

            if (readLevel) {
                this.controlSelect(scope, 'edit', readLevel, true);
                this.controlSelect(scope, 'stream', readLevel, true);

                if (!editLevel) {
                    this.controlSelect(scope, 'delete', readLevel, true);
                }
            }

            if (editLevel) {
                this.controlSelect(scope, 'delete', editLevel, true);
            }
        });

        this.fieldTableDataList.forEach(scopeItem => {
            scopeItem.list.forEach(fieldItem => this.setupFormField(scopeItem.name, fieldItem));
        });

        return Promise.all(promises);
    }

    /**
     * @private
     * @param {string} scope
     * @param {{
     *     name: string,
     *     list: {
     *         action: 'read'|'edit',
     *         value: 'yes'|'no',
     *         name: string,
     *     }[],
     * }} fieldItem
     */
    async setupFormField(scope, fieldItem) {
        const promises = [];

        const field = fieldItem.name;

        const defs = this.formModel.defs;

        fieldItem.list.forEach(actionItem => {
            const name = actionItem.name;

            defs.fields[name] = {
                type: 'enum',
                options: ['yes', 'no'],
                translation: 'Role.options.levelList',
                style: this.styleMap,
            };

            this.formModel.set(name, actionItem.value, {silent: true});

            const view = new CustomEnumFieldView({
                name: name,
                model: this.formModel,
                mode: this.mode,
                inlineEditDisabled: true,
                recordHelper: this.formRecordHelper,
            });

            this.enumViews[name] = view;

            promises.push(
                this.assignView(name, view, `div[data-name="${name}"]`)
            );

            if (actionItem.action === 'read') {
                this.listenTo(this.formModel, `change:${scope}-${field}-read`, (m, value) => {
                    this.controlFieldEditSelect(scope, field, value, true);
                });
            }
        });

        if (fieldItem.list.length) {
            const readLevel = this.formModel.attributes[`${scope}-${field}-read`];

            if (readLevel) {
                this.controlFieldEditSelect(scope, field, readLevel);
            }
        }

        await Promise.all(promises);
    }

    /**
     * @private
     */
    setupData() {
        this.acl = {};

        if (this.options.acl) {
            this.acl.data = this.options.acl.data;
        } else {
            this.acl.data = Espo.Utils.cloneDeep(this.model.attributes.data || {});
        }

        if (this.options.acl) {
            this.acl.fieldData = this.options.acl.fieldData;
        } else {
            this.acl.fieldData = Espo.Utils.cloneDeep(this.model.attributes.fieldData || {});
        }

        this.setupScopeList();
        this.setupFieldTableDataList();
    }

    /**
     * @private
     * @return {string[]} scopeList
     */
    getSortedScopeList() {
        const moduleList = [null, 'Crm'];

        const scopes = /** @type {Object.<{module: string}>} */this.getMetadata().get('scopes');

        Object.keys(scopes).forEach(scope => {
            const module = scopes[scope].module;

            if (!module || module === 'Custom' || moduleList.includes(module)) {
                return;
            }

            moduleList.push(module);
        });

        moduleList.push('Custom');

        return Object.keys(scopes)
            .sort((v1, v2) => {
                const module1 = scopes[v1].module || null;
                const module2 = scopes[v2].module || null;

                if (module1 !== module2) {
                    const index1 = moduleList.findIndex(m => m === module1);
                    const index2 = moduleList.findIndex(m => m === module2);

                    return index1 - index2;
                }

                return this.translate(v1, 'scopeNamesPlural')
                    .localeCompare(this.translate(v2, 'scopeNamesPlural'));
            });
    }

    /**
     * @private
     */
    setupScopeList() {
        this.aclTypeMap = {};
        this.scopeList = [];

        this.getSortedScopeList().forEach(scope => {
            if (this.getMetadata().get(`scopes.${scope}.disabled`)) {
                return;
            }

            const acl = this.getMetadata().get(`scopes.${scope}.acl`);

            if (acl) {
                this.scopeList.push(scope);
                this.aclTypeMap[scope] = acl;

                if (acl === true) {
                    this.aclTypeMap[scope] = 'record';
                }
            }
        });
    }

    /**
     * @private
     */
    setupFieldTableDataList() {
        this.fieldTableDataList = [];

        this.scopeList.forEach(scope => {
            const defs = /** @type {Record} */this.getMetadata().get(`scopes.${scope}`) || {};

            if (!defs.entity || defs.aclFieldLevelDisabled) {
                return;
            }

            if (this.isAclFieldLevelDisabledForScope(scope)) {
                return;
            }

            if (!(scope in this.acl.fieldData)) {
                if (this.mode === 'edit') {
                    this.fieldTableDataList.push({
                        name: scope,
                        list: [],
                    });

                    return;
                }

                return;
            }

            const scopeData = this.acl.fieldData[scope];
            const fieldList = this.getFieldManager().getEntityTypeFieldList(scope);

            this.getLanguage().sortFieldList(scope, fieldList);

            const fieldDataList = [];

            fieldList.forEach(field => {
                if (!(field in scopeData)) {
                    return;
                }

                const list = [];

                this.fieldActionList.forEach(action => {
                    list.push({
                        name: `${scope}-${field}-${action}`,
                        action: action,
                        value: scopeData[field][action] || 'yes',
                    })
                });

                if (this.mode === 'detail' && !list.length) {
                    return;
                }

                fieldDataList.push({
                    name: field,
                    list: list,
                });
            });

            this.fieldTableDataList.push({
                name: scope,
                list: fieldDataList,
            });
        });
    }

    /**
     * @private
     * @param {string} scope
     * @return {boolean}
     */
    isAclFieldLevelDisabledForScope(scope) {
        return !!this.getMetadata().get(`scopes.${scope}.aclFieldLevelDisabled`);
    }

    /**
     * @param {string} [onlyScope]
     * @return {Object.<string, Object.<string, string>|false|true>}
     */
    fetchScopeData(onlyScope) {
        const data = {};

        const scopeList = this.scopeList;
        const actionList = this.actionList;
        const aclTypeMap = this.aclTypeMap;

        for (const scope of scopeList) {
            if (onlyScope && scope !== onlyScope) {
                continue;
            }

            const value = this.formModel.attributes[scope] || 'not-set';

            if (!onlyScope && value === 'not-set') {
                continue;
            }

            if (!onlyScope && value === 'disabled') {
                data[scope] = false;

                continue;
            }

            let scopeData = true;

            if (aclTypeMap[scope] !== 'boolean') {
                scopeData = {};

                for (const j in actionList) {
                    const action = actionList[j];

                    const value = this.formModel.attributes[`${scope}-${action}`];

                    if (value === undefined) {
                        continue;
                    }

                    scopeData[action] = value;
                }
            }

            data[scope] = scopeData;
        }

        return data;
    }

    /**
     * @return {Object.<string, Object.<string, Record.<string, string>|false>>}
     */
    fetchFieldData() {
        const data = {};

        this.fieldTableDataList.forEach(scopeData => {
            const scopeValueData = {};
            const scope = scopeData.name;

            scopeData.list.forEach(fieldData => {
                const field = fieldData.name;
                const fieldValueData = {};

                this.fieldActionList.forEach(action => {
                    const name = `${scope}-${fieldData.name}-${action}`;

                    const value = this.formModel.attributes[name]

                    if (value === undefined) {
                        return;
                    }

                    fieldValueData[action] = value;
                });

                scopeValueData[field] = fieldValueData;
            });

            data[scope] = scopeValueData;
        });

        return data;
    }

    afterRender() {
        this.$quickSearch = this.$el.find('input[data-name="quick-search"]');

        if (this.mode === 'edit' || this.mode === 'detail') {
            this.initStickyHeader('scope');
            this.initStickyHeader('field');
        }
    }

    /**
     * @private
     * @param {string} scope
     * @param {string} field
     * @param {string} limitValue
     * @param {boolean} [dontChange]
     */
    controlFieldEditSelect(scope, field, limitValue, dontChange) {
        const attribute = `${scope}-${field}-edit`;

        let value = this.formModel.attributes[attribute];

        if (
            !dontChange &&
            this.levelList.indexOf(value) < this.levelList.indexOf(limitValue)
        ) {
            value = limitValue;
        }

        const options = this.fieldLevelList
            .filter(item => this.levelList.indexOf(item) >= this.levelList.indexOf(limitValue));

        if (!dontChange) {
            this.formModel.set(attribute, value);
        }

        this.formRecordHelper.setFieldOptionList(attribute, options);

        const view = this.enumViews[attribute];

        if (view) {
            view.setOptionList(options);
        }
    }

    /**
     * @private
     * @param {string} scope
     * @param {string} action
     * @param {string} limitValue
     * @param {boolean} [dontChange]
     */
    controlSelect(scope, action, limitValue, dontChange) {
        const attribute = `${scope}-${action}`;

        let value = this.formModel.attributes[attribute];

        if (
            !dontChange &&
            this.levelList.indexOf(value) < this.levelList.indexOf(limitValue)
        ) {
            value = limitValue;
        }

        const options = this.getLevelList(scope, action)
            .filter(item => this.levelList.indexOf(item) >= this.levelList.indexOf(limitValue));

        if (!dontChange) {
            setTimeout(() => this.formModel.set(attribute, value), 0);
        }

        this.formRecordHelper.setFieldOptionList(attribute, options);

        const view = this.enumViews[attribute];

        if (view) {
            view.setOptionList(options);
        }
    }

    /**
     * @private
     * @param {string} scope
     */
    showAddFieldModal(scope) {
        const ignoreFieldList = Object.keys(this.acl.fieldData[scope] || {});

        this.createView('dialog', 'views/role/modals/add-field', {
            scope: scope,
            ignoreFieldList: ignoreFieldList,
            type: this.type,
        }, /** import('views/role/modals/add-field').default */view => {
            view.render();

            this.listenTo(view, 'add-fields', async (/** string[] */fields) => {
                view.close();

                const scopeData = this.fieldTableDataList.find(it => it.name === scope);

                if (!scopeData) {
                    return;
                }

                const promises = [];

                fields.filter(field => !scopeData.list.find(it => it.name === field))
                    .forEach(field => {
                        const item = {
                            name: field,
                            list: [
                                {
                                    name: `${scope}-${field}-read`,
                                    action: 'read',
                                    value: 'no',
                                },
                                {
                                    name: `${scope}-${field}-edit`,
                                    action: 'edit',
                                    value: 'no',
                                },
                            ]
                        };

                        scopeData.list.unshift(item);

                        promises.push(
                            this.setupFormField(scope, item)
                        );
                    });

                await Promise.all(promises);

                await this.reRenderPreserveSearch();

                this.trigger('change');
            });
        });
    }

    /**
     * @private
     * @param {string} scope
     * @param {string} field
     */
    async removeField(scope, field) {
        const attributeRead = `${scope}-${field}-read`;
        const attributeEdit = `${scope}-${field}-edit`;

        delete this.enumViews[attributeRead];
        delete this.enumViews[attributeEdit];

        this.clearView(attributeRead);
        this.clearView(attributeEdit);

        this.formModel.unset(attributeRead);
        this.formModel.unset(attributeEdit);

        delete this.formModel.defs.fields[attributeRead];
        delete this.formModel.defs.fields[attributeEdit];

        const scopeData = this.fieldTableDataList.find(it => it.name === scope);

        if (!scopeData) {
            return;
        }

        const index = scopeData.list.findIndex(it => it.name === field);

        if (index === -1) {
            return;
        }

        scopeData.list.splice(index, 1);

        await this.reRenderPreserveSearch();

        this.trigger('change');
    }

    /**
     * @private
     */
    async reRenderPreserveSearch() {
        const searchText = this.$quickSearch.val();

        const scrollY = window.scrollY;

        await this.reRender();

        window.scrollTo({top: scrollY});

        this.$quickSearch.val(searchText);
        this.processQuickSearch(searchText);

    }

    /**
     * @private
     * @param {string} type
     */
    initStickyHeader(type) {
        const $sticky = this.$el.find(`.sticky-header-${type}`);

        const screenWidthXs = this.getThemeManager().getParam('screenWidthXs');

        const $buttonContainer = $('.detail-button-container');
        const $table = this.$el.find(`table.${type}-level`);

        if (!$table.length) {
            return;
        }

        if (!$buttonContainer.length) {
            return;
        }

        const navbarHeight = this.getThemeManager().getParam('navbarHeight') *
            this.getThemeManager().getFontSizeFactor();

        const handle = () => {
            if (window.innerWidth < screenWidthXs) {
                $sticky.addClass('hidden');

                return;
            }

            const stickTopPosition = $buttonContainer.get(0).getBoundingClientRect().top +
                $buttonContainer.outerHeight();

            let topEdge = $table.position().top;

            topEdge -= $buttonContainer.height();
            topEdge += $table.find('tr > th').height();
            topEdge -= navbarHeight;

            const bottomEdge = topEdge + $table.outerHeight(true) - $buttonContainer.height();
            const scrollTop = window.scrollY;
            const width = $table.width();

            if (scrollTop > topEdge && scrollTop < bottomEdge) {
                $sticky.css({
                    position: 'fixed',
                    marginTop: stickTopPosition + 'px',
                    top: 0,
                    width: width + 'px',
                    marginLeft: '1px',
                });

                $sticky.removeClass('hidden');
            } else {
                $sticky.addClass('hidden');
            }
        };

        const $window = $(window);

        $window.off(`scroll.${type}-${this.cid}`);
        $window.on(`scroll.${type}-${this.cid}`, handle);

        $window.off(`resize.${type}-${this.cid}`);
        $window.on(`resize.${type}-${this.cid}`, handle);

        handle();
    }

    /**
     * @private
     * @param {string} scope
     * @param {string} action
     * @return {EnumFieldView}
     */
    getScopeActionView(scope, action) {
        return this.getView(`${scope}-${action}`);
    }

    /**
     * @private
     * @param {string} scope
     */
    hideScopeActions(scope) {
        this.actionList.forEach(action => {
            const view = this.getScopeActionView(scope, action);

            if (!view) {
                return;
            }

            view.hide();

            const name = `${scope}-${action}`;

            this.formRecordHelper.setFieldStateParam(name, 'hidden', true);
        });
    }

    /**
     * @private
     * @param {string} scope
     */
    showScopeActions(scope) {
        this.actionList.forEach(action => {
            const view = this.getScopeActionView(scope, action);

            if (!view) {
                return;
            }

            view.show();

            const name = `${scope}-${action}`;

            this.formRecordHelper.setFieldStateParam(name, 'hidden', false);
        });
    }

    /**
     * @private
     * @param {string} scope
     */
    onSelectAccess(scope) {
        const value = this.formModel.attributes[scope];

        if (value !== 'enabled') {
            const fetchedData = this.fetchScopeData(scope);

            this.hideScopeActions(scope);

            delete this.scopeLevelMemory[scope];

            if (scope in fetchedData) {
                this.scopeLevelMemory[scope] = fetchedData[scope] || {};
            }

            return;
        }

        this.showScopeActions(scope);

        const attributes = {};

        this.actionList.forEach(action => {
            const memoryData = this.scopeLevelMemory[scope] || {};
            const levelInMemory = memoryData[action];

            let level = levelInMemory || this.defaultLevels[action];

            if (!level) {
                level = this.getLevelList(scope, action)[0];
            }

            if (!levelInMemory && this.lowestLevelByDefault) {
                level = [...this.getLevelList(scope, action)].pop();
            }

            attributes[`${scope}-${action}`] = level;
        });

        // Need a timeout as it's processed within a change callback.
        setTimeout(() => this.formModel.set(attributes), 0);
    }

    /**
     * @private
     * @param {string} text
     */
    processQuickSearch(text) {
        text = text.trim();

        if (!text) {
            this.$el.find('table tr.item-row').removeClass('hidden');

            return;
        }

        const matchedList = [];

        const lowerCaseText = text.toLowerCase();

        this.scopeList.forEach(item => {
            let matched = false;

            const translation = this.getLanguage().translate(item, 'scopeNamesPlural');

            if (
                translation.toLowerCase().indexOf(lowerCaseText) === 0 ||
                item.toLowerCase().indexOf(lowerCaseText) === 0
            ) {
                matched = true;
            }

            if (!matched) {
                const wordList = translation.split(' ')
                    .concat(translation.split(' '));

                wordList.forEach((word) => {
                    if (word.toLowerCase().indexOf(lowerCaseText) === 0) {
                        matched = true;
                    }
                });
            }

            if (matched) {
                matchedList.push(item);
            }
        });

        if (matchedList.length === 0) {
            this.$el.find('table tr.item-row').addClass('hidden');

            return;
        }

        this.$el.find('table tr.item-row[data-name="_"]').addClass('hidden');

        this.scopeList.forEach(/** string */item => {
            const $row = this.$el.find(`table tr.item-row[data-name="${item}"]`);

            if (!matchedList.includes(item)) {
                $row.addClass('hidden');

                return;
            }

            $row.removeClass('hidden');
        });
    }
}

class CustomEnumFieldView extends EnumFieldView {

    nativeSelect = true
}

export default RoleRecordTableView;
