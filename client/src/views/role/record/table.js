/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
import Select from 'ui/select';

class RoleRecordTableView extends View {

    template = 'role/table'

    /** @type {string[]} */
    scopeList
    type = 'acl'
    /** @type {'detail'|'string'} */
    mode = 'detail'
    lowestLevelByDefault = false

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
        /** @this RoleRecordTableView */
        'change select[data-type="access"]': function (e) {
            const $current = $(e.currentTarget);

            const scope = $current.attr('name');
            const value = $current.val();

            this.onSelectAccess(scope, value);
        },
    }

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
                    const allowedActionList = /** @type {string[]} */
                        this.getMetadata().get(['scopes', scope, this.type + 'ActionList']);

                    if (allowedActionList) {
                        if (!allowedActionList.includes(action)) {
                            list.push({
                                action: action,
                                levelList: false,
                                level: null,
                            });

                            return;
                        }
                    }

                    if (action === 'stream') {
                        if (!this.getMetadata().get(`scopes.${scope}.stream`)) {
                            list.push({
                                action: 'stream',
                                levelList: false,
                                level: null,
                            });

                            return;
                        }
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

                    list.push({
                        level: level,
                        name: scope + '-' + action,
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

    getLevelList(scope, action) {
        if (this.booleanActionList.includes(action)) {
            return this.booleanLevelList;
        }

        const type = this.aclTypeMap[scope];

        return this.getMetadata().get(['scopes', scope, this.type + 'ActionLevelListMap', action]) ||
            this.getMetadata().get(['scopes', scope, this.type + 'LevelList']) ||
            this.levelListMap[type] ||
            [];
    }

    setup() {
        this.mode = this.options.mode || 'detail';
        this.final = this.options.final || false;

        this.scopeLevelMemory = {};

        this.setupData();

        this.listenTo(this.model, 'change', () => {
            if (this.model.hasChanged('data') || this.model.hasChanged('fieldData')) {
                this.setupData();
            }
        });

        this.listenTo(this.model, 'sync', () => {
            this.setupData();

            if (this.isRendered()) {
                this.reRenderPreserveSearch();
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

    setupData() {
        this.acl = {};

        if (this.options.acl) {
            this.acl.data = this.options.acl.data;
        } else {
            this.acl.data = Espo.Utils.cloneDeep(this.model.get('data') || {});
        }

        if (this.options.acl) {
            this.acl.fieldData = this.options.acl.fieldData;
        } else {
            this.acl.fieldData = Espo.Utils.cloneDeep(this.model.get('fieldData') || {});
        }

        this.setupScopeList();
        this.setupFieldTableDataList();
    }

    /**
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

    setupFieldTableDataList() {
        /**
         * @type {
         *     {
         *         name: string,
         *         list: {
         *             name: string,
         *             list: {name: 'read'|'edit', value: 'yes'|'no'}[],
         *          }[],
         *     }[]
         * } */
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
                        name: action,
                        value: scopeData[field][action] || 'yes',
                    })
                });

                if (this.mode === 'detail') {
                    if (!list.length) {
                        return;
                    }
                }

                fieldDataList.push({
                    name: field,
                    list: list
                });
            });

            this.fieldTableDataList.push({
                name: scope,
                list: fieldDataList,
            });
        });
    }

    isAclFieldLevelDisabledForScope(scope) {
        return !!this.getMetadata().get(`scopes.${scope}.aclFieldLevelDisabled`);
    }

    /**
     *
     * @param {string} [onlyScope]
     * @return {Object.<Record>}
     */
    fetchScopeData(onlyScope) {
        const data = {};

        const scopeList = this.scopeList;
        const actionList = this.actionList;
        const aclTypeMap = this.aclTypeMap;

        const $table = this.$el.find(`table.scope-level`);

        for (const i in scopeList) {
            const scope = scopeList[i];

            if (onlyScope && scope !== onlyScope) {
                continue;
            }

            const $rows = $table.find(`tr[data-name="${scope}"]`);

            const value = $rows.find(`select[name="${scope}"]`).val();

            if (!onlyScope && value === 'not-set') {
                continue;
            }

            if (!onlyScope && value === 'disabled') {
                data[scope] = false;

                continue;
            }

            let o = true;

            if (aclTypeMap[scope] !== 'boolean') {
                o = {};

                for (const j in actionList) {
                    const action = actionList[j];

                    const value = $rows.find(`select[name="${scope}-${action}"]`).val();

                    if (value === undefined) {
                        continue;
                    }

                    o[action] = value;
                }
            }

            data[scope] = o;
        }

        return data;
    }

    fetchFieldData() {
        const data = {};

        this.fieldTableDataList.forEach(scopeData => {
            const scopeObj = {};
            const scope = scopeData.name;

            const $rows = this.$el.find(`table.field-level tr[data-name="${scope}"]`);

            scopeData.list.forEach(fieldData => {
                const field = fieldData.name;
                const fieldObj = {};

                this.fieldActionList.forEach(action =>{
                    const $select = $rows
                        .find(`select[data-scope="${scope}"][data-field="${field}"][data-action="${action}"]`);

                    if (!$select.length) {
                        return;
                    }

                    fieldObj[action] = $select.val();
                });

                scopeObj[field] = fieldObj;
            });

            data[scope] = scopeObj;
        });

        return data;
    }

    afterRender() {
        this.$quickSearch = this.$el.find('input[data-name="quick-search"]');

        if (this.mode === 'edit') {
            this.$el.find('select').each((i, el) => {
                Select.init(el);
            });

            this.scopeList.forEach(scope => {
                const $read = this.$el.find(`select[name="${scope}-read"]`);

                $read.on('change', () => {
                    const value = $read.val();

                    this.controlSelect(scope, 'edit', value);
                    this.controlSelect(scope, 'delete', value);
                    this.controlSelect(scope, 'stream', value);
                });

                const $edit = this.$el.find(`select[name="${scope}-edit"]`);

                $edit.on('change', () => {
                    const value = $edit.val();

                    this.controlSelect(scope, 'delete', value);
                });

                setTimeout(() => {
                    this.controlSelect(scope, 'edit', $read.val(), true);
                    this.controlSelect(scope, 'stream', $read.val(), true);
                    this.controlSelect(scope, 'delete', $edit.val(), true);
                }, 10);
            });

            this.fieldTableDataList.forEach(o => {
                const scope = o.name;

                o.list.forEach(f => {
                    const field = f.name;

                    const $read = this.$el
                        .find('select[data-scope="' + scope + '"][data-field="' + field + '"][data-action="read"]');

                    $read.on('change', () => {
                        const value = $read.val();

                        this.controlFieldEditSelect(scope, field, value);
                    });

                    this.controlFieldEditSelect(scope, field, $read.val(), true);
                });
            });
        }

        if (this.mode === 'edit' || this.mode === 'detail') {
            this.initStickyHeader('scope');
            this.initStickyHeader('field');
        }
    }

    controlFieldEditSelect(scope, field, limitValue, dontChange) {
        const $select = this.$el.find(`select[data-scope="${scope}"][data-field="${field}"][data-action="edit"]`);

        if (!$select.length) {
            return;
        }

        let value = $select.val();

        if (
            !dontChange &&
            this.levelList.indexOf(value) < this.levelList.indexOf(limitValue)
        ) {
            value = limitValue;
        }

        const options = this.fieldLevelList
            .filter(item => {
                return this.levelList.indexOf(item) >= this.levelList.indexOf(limitValue);
            })
            .map(item => {
                return {
                    value: item,
                    text: this.getLanguage().translateOption(item, 'levelList', 'Role'),
                };
            });

        if (!dontChange) {
            // Prevents issues.
            Select.destroy($select);
            Select.init($select);
        }

        Select.setValue($select, '');
        Select.setOptions($select, options);
        Select.setValue($select, value);
    }

    controlSelect(scope, action, limitValue, dontChange) {
        const $select = this.$el.find(`select[name="${scope}-${action}"]`);

        if (!$select.length) {
            return;
        }

        let value = $select.val();

        if (
            !dontChange &&
            this.levelList.indexOf(value) < this.levelList.indexOf(limitValue)
        ) {
            value = limitValue;
        }

        const options = this.getLevelList(scope, action)
            .filter(item => {
                return this.levelList.indexOf(item) >= this.levelList.indexOf(limitValue);
            }).
            map(item => {
                return {
                    value: item,
                    text: this.getLanguage().translateOption(item, 'levelList', 'Role'),
                };
            });

        if (!dontChange) {
            // Prevents issues.
            Select.destroy($select);
            Select.init($select);
        }

        Select.setValue($select, '');
        Select.setOptions($select, options);
        Select.setValue($select, value);
    }

    showAddFieldModal(scope) {
        this.trigger('change');

        const ignoreFieldList = Object.keys(this.acl.fieldData[scope] || {});

        this.createView('dialog', 'views/role/modals/add-field', {
            scope: scope,
            ignoreFieldList: ignoreFieldList,
            type: this.type,
        }, view => {
            view.render();

            this.listenTo(view, 'add-fields', /** string[] */fields => {
                this.clearView('dialog');

                const scopeData = this.fieldTableDataList.find(it => it.name === scope);

                if (!scopeData) {
                    return;
                }

                fields.filter(field => !scopeData.list.find(it => it.name === field))
                    .forEach(field => {
                        scopeData.list.unshift({
                            name: field,
                            list: [
                                {
                                    name: 'read',
                                    value: 'no',
                                },
                                {
                                    name: 'edit',
                                    value: 'no',
                                },
                            ]
                        });
                    });

                this.reRenderPreserveSearch();
            });
        });
    }

    removeField(scope, field) {
        this.trigger('change');

        this.fieldTableDataList.forEach(scopeData => {
            if (scopeData.name !== scope) {
                return;
            }

            let index = -1;

            scopeData.list.forEach((d, i) => {
                if (d.name === field) {
                    index = i;
                }
            });

            if (~index) {
                scopeData.list.splice(index, 1);

                this.reRenderPreserveSearch();
            }
        });
    }

    reRenderPreserveSearch() {
        const searchText = this.$quickSearch.val();

        this.reRender()
            .then(() => {
                this.$quickSearch.val(searchText);
                this.processQuickSearch(searchText);
            });
    }

    initStickyHeader(type) {
        const $sticky = this.$el.find('.sticky-header-' + type);
        const $window = $(window);

        const screenWidthXs = this.getThemeManager().getParam('screenWidthXs');

        const $buttonContainer = $('.detail-button-container');
        const $table = this.$el.find('table.' + type + '-level');

        if (!$table.length) {
            return;
        }

        if (!$buttonContainer.length) {
            return;
        }

        const handle = () => {
            if ($(window.document).width() < screenWidthXs) {
                $sticky.addClass('hidden');

                return;
            }

            const stickTopPosition = $buttonContainer.get(0).getBoundingClientRect().top +
                $buttonContainer.outerHeight();

            let topEdge = $table.position().top;

            topEdge -= $buttonContainer.height();
            topEdge += $table.find('tr > th').height();
            topEdge -= this.getThemeManager().getParam('navbarHeight');

            const bottomEdge = topEdge + $table.outerHeight(true) - $buttonContainer.height();
            const scrollTop = $window.scrollTop();
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

        $window.off('scroll.' + type + '-' + this.cid);
        $window.on('scroll.' + type + '-' + this.cid, handle);

        $window.off('resize.' + type + '-' + this.cid);
        $window.on('resize.' + type + '-' + this.cid, handle);
    }

    /**
     * @param {string} scope
     * @param {string} value
     */
    onSelectAccess(scope, value) {
        const $dropdowns = this.$el.find(`.scope-level select[data-scope="${scope}"]`);

        if (value !== 'enabled') {
            const fetchedData = this.fetchScopeData(scope);

            $dropdowns.attr('disabled', 'disabled');
            $dropdowns.addClass('hidden');
            $dropdowns.parent().find('.selectize-control').addClass('hidden');

            delete this.scopeLevelMemory[scope];

            if (scope in fetchedData) {
                this.scopeLevelMemory[scope] = fetchedData[scope] || {};
            }

            return;
        }

        $dropdowns.removeAttr('disabled');
        $dropdowns.removeClass('hidden');
        $dropdowns.parent().find('.selectize-control').removeClass('hidden');

        this.actionList.forEach(action => {
            const $select = this.$el.find(`select[name="${scope}-${action}"]`);

            if (!$select.length) {
                return;
            }

            const memoryData = this.scopeLevelMemory[scope] || {};
            const levelInMemory = memoryData[action];

            let level = levelInMemory || this.defaultLevels[action];

            if (!level) {
                level = this.getLevelList(scope, action)[0];
            }

            if (!levelInMemory && this.lowestLevelByDefault) {
                level = [...this.getLevelList(scope, action)].pop();
            }

            Select.setValue($select, level);
            $select.trigger('change');
        });
    }

    processQuickSearch(text) {
        text = text.trim();

        if (!text) {
            this.$el.find('table tr.item-row').removeClass('hidden');

            return;
        }

        const matchedList = [];

        const lowerCaseText = text.toLowerCase();

        this.scopeList.forEach(/** string */item => {
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

export default RoleRecordTableView;
