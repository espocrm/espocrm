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

class RoleRecordTableView extends View {

    template = 'role/table'

    scopeList = null
    type = 'acl'
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

    colors = {
        yes: '#6BC924',
        all: '#6BC924',
        account: '#999900',
        contact: '#999900',
        team: '#999900',
        own: '#CC9900',
        no: '#F23333',
        enabled: '#6BC924',
        disabled: '#F23333',
        'not-set': '#A8A8A8',
    }

    data() {
        const data = {};

        data.editMode = this.mode === 'edit';
        data.actionList = this.actionList;
        data.accessList = this.accessList;
        data.fieldActionList = this.fieldActionList;
        data.fieldLevelList = this.fieldLevelList;
        data.colors = this.colors;

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
            const scope = $(e.currentTarget).attr('name');
            const $dropdowns = this.$el.find('select[data-scope="' + scope + '"]');

            if ($(e.currentTarget).val() === 'enabled') {
                $dropdowns.removeAttr('disabled');
                $dropdowns.removeClass('hidden');

                $dropdowns.each((i, select) => {
                    const $select = $(select);

                    if (this.lowestLevelByDefault) {
                        $select.find('option').last().prop('selected', true);
                    } else {
                        let setFirst = true;
                        const action = $select.data('role-action');
                        let defaultLevel = null;

                        if (action) {
                            defaultLevel = this.defaultLevels[action];
                        }

                        if (defaultLevel) {
                            const $option = $select.find('option[value="' + defaultLevel + '"]');

                            if ($option.length) {
                                $option.prop('selected', true);
                                setFirst = false;
                            }
                        }

                        if (setFirst) {
                            $select.find('option').first().prop('selected', true);
                        }
                    }

                    $select.trigger('change');

                    this.controlSelectColor($select);
                });
            } else {
                $dropdowns.attr('disabled', 'disabled');
                $dropdowns.addClass('hidden');
            }

            this.controlSelectColor($(e.currentTarget));
        },
        /** @this RoleRecordTableView */
        'change select.scope-action': function (e) {
            this.controlSelectColor($(e.currentTarget));
        },
        /** @this RoleRecordTableView */
        'change select.field-action': function (e) {
            this.controlSelectColor($(e.currentTarget));
        },
    }

    getTableDataList() {
        const aclData = this.acl.data;
        const aclDataList = [];

        this.scopeList.forEach(scope => {
            let access = 'not-set';

            if (this.final) {
                access = 'enabled';
            }

            if (scope in aclData) {
                if (aclData[scope] === false) {
                    access = 'disabled';
                } else {
                    access = 'enabled';
                }
            }

            const list = [];
            const type = this.aclTypeMap[scope];

            if (this.aclTypeMap[scope] !== 'boolean') {
                this.actionList.forEach(action => {
                    const allowedActionList = this.getMetadata().get(['scopes', scope, this.type + 'ActionList']);

                    if (allowedActionList) {
                        if (!~allowedActionList.indexOf(action)) {
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

                    let level = 'no';

                    if (~this.booleanActionList.indexOf(action)) {
                        level = 'no';
                    }

                    if (scope in aclData) {
                        if (access === 'enabled') {
                            if (aclData[scope] !== true) {
                                if (action in aclData[scope]) {
                                    level = aclData[scope][action];
                                }
                            }
                        } else {
                            level = 'no';
                        }
                    }

                    let levelList =
                        this.getMetadata().get(['scopes', scope, this.type + 'ActionLevelListMap', action]) ||
                        this.getMetadata().get(['scopes', scope, this.type + 'LevelList']) ||
                        this.levelListMap[type] ||
                        [];

                    if (~this.booleanActionList.indexOf(action)) {
                        levelList = this.booleanLevelList;
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

    setup() {
        this.mode = this.options.mode || 'detail';
        this.final = this.options.final || false;

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

    setupScopeList() {
        this.aclTypeMap = {};
        this.scopeList = [];

        const scopeListAll = Object.keys(this.getMetadata().get('scopes'))
            .sort((v1, v2) => {
                return this.translate(v1, 'scopeNamesPlural')
                    .localeCompare(this.translate(v2, 'scopeNamesPlural'));
            });

        scopeListAll.forEach(scope => {
            if (this.getMetadata().get('scopes.' + scope + '.disabled')) {
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
        this.fieldTableDataList = [];

        this.scopeList.forEach(scope => {
            const d = this.getMetadata().get(`scopes.${scope}`) || {};

            if (!d.entity) {
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

    fetchScopeData() {
        const data = {};

        const scopeList = this.scopeList;
        const actionList = this.actionList;
        const aclTypeMap = this.aclTypeMap;

        for (const i in scopeList) {
            const scope = scopeList[i];

            if (this.$el.find('select[name="' + scope + '"]').val() === 'not-set') {
                continue;
            }

            if (this.$el.find('select[name="' + scope + '"]').val() === 'disabled') {
                data[scope] = false;
            } else {
                let o = true;

                if (aclTypeMap[scope] !== 'boolean') {
                    o = {};

                    for (const j in actionList) {
                        const action = actionList[j];

                        o[action] = this.$el.find('select[name="' + scope + '-' + action + '"]').val();
                    }
                }

                data[scope] = o;
            }
        }

        return data;
    }

    fetchFieldData() {
        const data = {};

        this.fieldTableDataList.forEach(scopeData => {
            const scopeObj = {};
            const scope = scopeData.name;

            scopeData.list.forEach(fieldData => {
                const field = fieldData.name;
                const fieldObj = {};

                this.fieldActionList.forEach(action =>{
                    const $select = this.$el
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
            this.scopeList.forEach(scope => {
                const $read = this.$el.find('select[name="' + scope + '-read"]');

                $read.on('change', () => {
                    const value = $read.val();

                    this.controlEditSelect(scope, value);
                    this.controlDeleteSelect(scope, value);
                    this.controlStreamSelect(scope, value);
                });

                const $edit = this.$el.find('select[name="' + scope + '-edit"]');

                $edit.on('change', () => {
                    const value = $edit.val();

                    this.controlDeleteSelect(scope, value);
                });

                this.controlEditSelect(scope, $read.val(), true);
                this.controlStreamSelect(scope, $read.val(), true);
                this.controlDeleteSelect(scope, $edit.val(), true);
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

            this.setSelectColors();
        }

        if (this.mode === 'edit' || this.mode === 'detail') {
            this.initStickyHeader('scope');
            this.initStickyHeader('field');
        }
    }

    controlFieldEditSelect(scope, field, value, dontChange) {
        const $edit = this.$el.find(`select[data-scope="${scope}"][data-field="${field}"][data-action="edit"]`);

        if (!dontChange) {
            if (this.fieldLevelList.indexOf($edit.val()) < this.fieldLevelList.indexOf(value)) {
                $edit.val(value);
            }
        }

        $edit.find('option').each((i, o) => {
            const $o = $(o);

            if (this.fieldLevelList.indexOf($o.val()) < this.fieldLevelList.indexOf(value)) {
                $o.attr('disabled', 'disabled');
            } else {
                $o.removeAttr('disabled');
            }
        });

        this.controlSelectColor($edit);
    }

    controlEditSelect(scope, value, dontChange) {
        const $edit = this.$el.find('select[name="' + scope + '-edit"]');

        if (!dontChange) {
            if (this.levelList.indexOf($edit.val()) < this.levelList.indexOf(value)) {
                $edit.val(value);
            }
        }

        $edit.find('option').each((i, o) => {
            const $o = $(o);

            if (this.levelList.indexOf($o.val()) < this.levelList.indexOf(value)) {
                $o.attr('disabled', 'disabled');
            } else {
                $o.removeAttr('disabled');
            }
        });

        this.controlSelectColor($edit);
    }

    controlStreamSelect(scope, value, dontChange) {
        const $stream = this.$el.find('select[name="' + scope + '-stream"]');

        if (!dontChange) {
            if (this.levelList.indexOf($stream.val()) < this.levelList.indexOf(value)) {
                $stream.val(value);
            }
        }

        $stream.find('option').each((i, o) => {
            const $o = $(o);

            if (this.levelList.indexOf($o.val()) < this.levelList.indexOf(value)) {
                $o.attr('disabled', 'disabled');
            } else {
                $o.removeAttr('disabled');
            }
        });

        this.controlSelectColor($stream);
    }

    controlDeleteSelect(scope, value, dontChange) {
        const $delete = this.$el.find('select[name="' + scope + '-delete"]');

        if (!dontChange) {
            if (this.levelList.indexOf($delete.val()) < this.levelList.indexOf(value)) {
                $delete.val(value);
            }
        }

        $delete.find('option').each((i, o) => {
            const $o = $(o);

            if (this.levelList.indexOf($o.val()) < this.levelList.indexOf(value)) {
                $o.attr('disabled', 'disabled');
            } else {
                $o.removeAttr('disabled');
            }
        });

        this.controlSelectColor($delete);
    }

    showAddFieldModal(scope) {
        this.trigger('change');

        const ignoreFieldList = Object.keys(this.acl.fieldData[scope] || {});

        this.createView('addField', 'views/role/modals/add-field', {
            scope: scope,
            ignoreFieldList: ignoreFieldList,
            type: this.type,
        }, (view) => {
            view.render();

            this.listenTo(view, 'add-field', field => {
                view.close();

                this.fieldTableDataList.forEach(scopeData =>{
                    if (scopeData.name !== scope) {
                        return;
                    }

                    let found = false;

                    scopeData.list.forEach(d => {
                        if (d.name === field) {
                            found = true;
                        }
                    });

                    if (found) {
                        return;
                    }

                    scopeData.list.unshift({
                        name: field,
                        list: [
                            {
                                name: 'read',
                                value: 'yes',
                            },
                            {
                                name: 'edit',
                                value: 'yes',
                            },
                        ]
                    });
                });

                const searchText = this.$quickSearch.val();

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

    setSelectColors() {
        this.$el.find('select[data-type="access"]').each((i, el) => {
            const $select = $(el);

            this.controlSelectColor($select);
        });

        this.$el.find('select.scope-action').each((i, el) => {
            const $select = $(el);

            this.controlSelectColor($select);
        });

        this.$el.find('select.field-action').each((i, el) => {
            const $select = $(el);

            this.controlSelectColor($select);
        });
    }

    controlSelectColor($select) {
        const level = $select.val();
        let color = this.colors[level] || '';

        if (level === 'not-set') {
            color = '';
        }

        $select.css('color', color);

        $select.children().each((j, el) => {
            const $o = $(el);
            const level = $o.val();

            let color = this.colors[level] || '';

            if (level === 'not-set') {
                color = '';
            }

            if ($o.attr('disabled')) {
                color = '';
            }

            $o.css('color', color);
        });
    }

    processQuickSearch(text) {
        text = text.trim();

        //const $noData = this.$noData;

        //$noData.addClass('hidden');

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

            //$noData.removeClass('hidden');

            return;
        }

        this.scopeList.forEach(/** string */item => {
            const $row = this.$el.find(`table tr.item-row[data-name="${item}"]`);

            if (!~matchedList.indexOf(item)) {
                $row.addClass('hidden');

                return;
            }

            $row.removeClass('hidden');
        });
    }
}

export default RoleRecordTableView;
