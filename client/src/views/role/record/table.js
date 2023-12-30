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

define('views/role/record/table', ['view'], function (Dep) {

    return Dep.extend({

        template: 'role/table',

        scopeList: null,

        actionList: ['create', 'read', 'edit', 'delete', 'stream'],

        accessList: ['not-set', 'enabled', 'disabled'],

        fieldLevelList: ['yes', 'no'],

        fieldActionList: ['read', 'edit'],

        levelListMap: {
            'recordAllTeamOwnNo': ['all', 'team', 'own', 'no'],
            'recordAllTeamNo': ['all', 'team', 'no'],
            'recordAllOwnNo': ['all', 'own', 'no'],
            'recordAllNo': ['all', 'no'],
            'record': ['all', 'team', 'own', 'no'],
        },

        type: 'acl',

        levelList: ['yes', 'all', 'team', 'own', 'no'],

        booleanLevelList: ['yes', 'no'],

        booleanActionList: ['create'],

        defaultLevels: {
            delete: 'no',
        },

        colors: {
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
        },

        mode: 'detail',

        tableData: null,

        data: function () {
            var data = {};
            data.editMode = this.mode === 'edit';
            data.actionList = this.actionList;
            data.accessList = this.accessList;
            data.fieldActionList = this.fieldActionList;
            data.fieldLevelList = this.fieldLevelList;
            data.colors = this.colors;

            data.tableDataList = this.getTableDataList();
            data.fieldTableDataList = this.fieldTableDataList;

            var hasFieldLevelData = false;

            this.fieldTableDataList.forEach((d) => {
                if (d.list.length) {
                    hasFieldLevelData = true;
                }
            });

            data.hasFieldLevelData = hasFieldLevelData;

            return data;
        },

        events: {
            'click .action[data-action="addField"]': function (e) {
                var scope = $(e.currentTarget).data().scope;

                this.showAddFieldModal(scope);
            },
            'click .action[data-action="removeField"]': function (e) {
                var scope = $(e.currentTarget).data().scope;
                var field = $(e.currentTarget).data().field;

                this.removeField(scope, field);
            },
            'change select[data-type="access"]': function (e) {
                var scope = $(e.currentTarget).attr('name');
                var $dropdowns = this.$el.find('select[data-scope="' + scope + '"]');

                if ($(e.currentTarget).val() === 'enabled') {
                    $dropdowns.removeAttr('disabled');
                    $dropdowns.removeClass('hidden');

                    $dropdowns.each((i, select) => {
                        let $select = $(select);

                        if (this.lowestLevelByDefault) {
                            $select.find('option').last().prop('selected', true);
                        } else {
                            var setFirst = true;
                            var action = $select.data('role-action');
                            var defaultLevel = null;

                            if (action) {
                                defaultLevel = this.defaultLevels[action];
                            }

                            if (defaultLevel) {
                                var $option = $select.find('option[value="'+defaultLevel+'"]');
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
            'change select.scope-action': function (e) {
                this.controlSelectColor($(e.currentTarget));
            },
            'change select.field-action': function (e) {
                this.controlSelectColor($(e.currentTarget));
            },
        },

        getTableDataList: function () {
            var aclData = this.acl.data;
            var aclDataList = [];

            this.scopeList.forEach(scope => {

                var access = 'not-set';

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

                var list = [];
                var type = this.aclTypeMap[scope];

                if (this.aclTypeMap[scope] !== 'boolean') {
                    this.actionList.forEach(action => {
                        var allowedActionList = this.getMetadata().get(['scopes', scope, this.type + 'ActionList']);

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
                            if (!this.getMetadata().get('scopes.' + scope + '.stream')) {
                                list.push({
                                    action: 'stream',
                                    levelList: false,
                                    level: null,
                                });

                                return;
                            }
                        }

                        var level = 'no';

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

                        var levelList =
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
        },

        setup: function () {
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
                    this.reRender();
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
        },

        setupData: function () {
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
        },

        setupScopeList: function () {
            this.aclTypeMap = {};
            this.scopeList = [];

            var scopeListAll = Object.keys(this.getMetadata().get('scopes'))
                .sort((v1, v2) => {
                     return this.translate(v1, 'scopeNamesPlural')
                         .localeCompare(this.translate(v2, 'scopeNamesPlural'));
                });

            scopeListAll.forEach(scope => {
                if (this.getMetadata().get('scopes.' + scope + '.disabled')) {
                    return;
                }

                var acl = this.getMetadata().get('scopes.' + scope + '.acl');

                if (acl) {
                    this.scopeList.push(scope);
                    this.aclTypeMap[scope] = acl;

                    if (acl === true) {
                        this.aclTypeMap[scope] = 'record';
                    }
                }
            });
        },

        setupFieldTableDataList: function () {
            this.fieldTableDataList = [];

            this.scopeList.forEach(scope => {
                var d = this.getMetadata().get('scopes.' + scope) || {};

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

                var scopeData = this.acl.fieldData[scope];
                var fieldList = this.getFieldManager().getEntityTypeFieldList(scope);

                this.getLanguage().sortFieldList(scope, fieldList);

                var fieldDataList = [];

                fieldList.forEach(field => {
                    if (!(field in scopeData)) {
                        return;
                    }

                    var list = [];

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
        },

        fetchScopeData: function () {
            var data = {};

            var scopeList = this.scopeList;
            var actionList = this.actionList;
            var aclTypeMap = this.aclTypeMap;

            for (var i in scopeList) {
                var scope = scopeList[i];

                if (this.$el.find('select[name="' + scope + '"]').val() === 'not-set') {
                    continue;
                }

                if (this.$el.find('select[name="' + scope + '"]').val() === 'disabled') {
                    data[scope] = false;
                } else {
                    var o = true;

                    if (aclTypeMap[scope] !== 'boolean') {
                        o = {};

                        for (var j in actionList) {
                            var action = actionList[j];

                            o[action] = this.$el.find('select[name="' + scope + '-' + action + '"]').val();
                        }
                    }

                    data[scope] = o;
                }
            }

            return data;
        },

        fetchFieldData: function () {
            var data = {};

            this.fieldTableDataList.forEach(scopeData => {
                var scopeObj = {};
                var scope = scopeData.name;

                scopeData.list.forEach(fieldData => {
                    var field = fieldData.name;
                    var fieldObj = {};

                    this.fieldActionList.forEach(action =>{
                        var $select = this.$el
                            .find('select[data-scope="'+scope+'"][data-field="'+field+'"][data-action="'+action+'"]');

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
        },

        afterRender: function () {
            if (this.mode === 'edit') {
                this.scopeList.forEach(scope => {
                    var $read = this.$el.find('select[name="'+scope+'-read"]');

                    $read.on('change', () => {
                        var value = $read.val();

                        this.controlEditSelect(scope, value);
                        this.controlDeleteSelect(scope, value);
                        this.controlStreamSelect(scope, value);
                    });

                    var $edit = this.$el.find('select[name="'+scope+'-edit"]');

                    $edit.on('change', () => {
                        var value = $edit.val();

                        this.controlDeleteSelect(scope, value);
                    });

                    this.controlEditSelect(scope, $read.val(), true);
                    this.controlStreamSelect(scope, $read.val(), true);
                    this.controlDeleteSelect(scope, $edit.val(), true);
                });

                this.fieldTableDataList.forEach(o => {
                    var scope = o.name;

                    o.list.forEach(f => {
                        var field = f.name;

                        var $read = this.$el
                            .find('select[data-scope="'+scope+'"][data-field="'+field+'"][data-action="read"]');

                        $read.on('change', () => {
                            var value = $read.val();

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
        },

        controlFieldEditSelect: function (scope, field, value, dontChange) {
            var $edit = this.$el.find('select[data-scope="'+scope+'"][data-field="'+field+'"][data-action="edit"]');

            if (!dontChange) {
                if (this.fieldLevelList.indexOf($edit.val()) < this.fieldLevelList.indexOf(value)) {
                    $edit.val(value);
                }
            }

            $edit.find('option').each((i, o) => {
                var $o = $(o);

                if (this.fieldLevelList.indexOf($o.val()) < this.fieldLevelList.indexOf(value)) {
                    $o.attr('disabled', 'disabled');
                } else {
                    $o.removeAttr('disabled');
                }
            });

            this.controlSelectColor($edit);
        },

        controlEditSelect: function (scope, value, dontChange) {
            var $edit = this.$el.find('select[name="'+scope+'-edit"]');

            if (!dontChange) {
                if (this.levelList.indexOf($edit.val()) < this.levelList.indexOf(value)) {
                    $edit.val(value);
                }
            }

            $edit.find('option').each((i, o) => {
                var $o = $(o);

                if (this.levelList.indexOf($o.val()) < this.levelList.indexOf(value)) {
                    $o.attr('disabled', 'disabled');
                } else {
                    $o.removeAttr('disabled');
                }
            });

            this.controlSelectColor($edit);
        },

        controlStreamSelect: function (scope, value, dontChange) {
            var $stream = this.$el.find('select[name="'+scope+'-stream"]');

            if (!dontChange) {
                if (this.levelList.indexOf($stream.val()) < this.levelList.indexOf(value)) {
                    $stream.val(value);
                }
            }

            $stream.find('option').each((i, o) => {
                var $o = $(o);

                if (this.levelList.indexOf($o.val()) < this.levelList.indexOf(value)) {
                    $o.attr('disabled', 'disabled');
                } else {
                    $o.removeAttr('disabled');
                }
            });

            this.controlSelectColor($stream);
        },

        controlDeleteSelect: function (scope, value, dontChange) {
            var $delete = this.$el.find('select[name="'+scope+'-delete"]');

            if (!dontChange) {
                if (this.levelList.indexOf($delete.val()) < this.levelList.indexOf(value)) {
                    $delete.val(value);
                }
            }

            $delete.find('option').each((i, o) => {
                var $o = $(o);

                if (this.levelList.indexOf($o.val()) < this.levelList.indexOf(value)) {
                    $o.attr('disabled', 'disabled');
                } else {
                    $o.removeAttr('disabled');
                }
            });

            this.controlSelectColor($delete);
        },

        showAddFieldModal: function (scope) {
            this.trigger('change');

            var ignoreFieldList = Object.keys(this.acl.fieldData[scope] || {});

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

                        var found = false;

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
                                    value: 'yes'
                                },
                                {
                                    name: 'edit',
                                    value: 'yes'
                                }
                            ]
                        });
                    });

                    this.reRender();
                });
            });
        },

        removeField: function (scope, field) {
            this.trigger('change');

            this.fieldTableDataList.forEach(scopeData => {
                if (scopeData.name !== scope) {
                    return;
                }

                var index = -1;

                scopeData.list.forEach((d, i) => {
                    if (d.name === field) {
                        index = i;
                    }
                });

                if (~index) {
                    scopeData.list.splice(index, 1);

                    this.reRender();
                }
            });
        },

        initStickyHeader: function (type) {
            var $sticky = this.$el.find('.sticky-header-' + type);
            var $window = $(window);

            var screenWidthXs = this.getThemeManager().getParam('screenWidthXs');

            var $buttonContainer = $('.detail-button-container');
            var $table = this.$el.find('table.' + type + '-level');

            if (!$table.length) {
                return;
            }

            if (!$buttonContainer.length) {
                return;
            }

            var handle = () => {
                if ($(window.document).width() < screenWidthXs) {
                    $sticky.addClass('hidden');

                    return;
                }

                let stickTopPosition = $buttonContainer.get(0).getBoundingClientRect().top +
                    $buttonContainer.outerHeight();

                let topEdge = $table.position().top;

                topEdge -= $buttonContainer.height();
                topEdge += $table.find('tr > th').height();
                topEdge -= this.getThemeManager().getParam('navbarHeight');

                let bottomEdge = topEdge + $table.outerHeight(true) - $buttonContainer.height();
                let scrollTop = $window.scrollTop();
                let width = $table.width();

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
        },

        setSelectColors: function () {
            this.$el.find('select[data-type="access"]').each((i, el) => {
                var $select = $(el);
                this.controlSelectColor($select);
            });

            this.$el.find('select.scope-action').each((i, el) => {
                var $select = $(el);
                this.controlSelectColor($select);
            });

            this.$el.find('select.field-action').each((i, el) => {
                var $select = $(el);
                this.controlSelectColor($select);
            });
        },

        controlSelectColor: function ($select) {
            var level = $select.val();
            var color = this.colors[level] || '';

            if (level === 'not-set') {
                color = '';
            }

            $select.css('color', color);

            $select.children().each((j, el) => {
                var $o = $(el);
                var level = $o.val();

                var color = this.colors[level] || '';

                if (level === 'not-set') {
                    color = '';
                }

                if ($o.attr('disabled')) {
                    color = '';
                }

                $o.css('color', color);
            });
        },
    });
});
