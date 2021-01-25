/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/role/record/table', 'view', function (Dep) {

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
            this.fieldTableDataList.forEach(function (d) {
                if (d.list.length) {
                    hasFieldLevelData = true;
                    return;
                }
            }, this);
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

                if ($(e.currentTarget).val() == 'enabled') {
                    $dropdowns.removeAttr('disabled');
                    $dropdowns.removeClass('hidden');
                    $dropdowns.each(function (i, select) {
                        $select = $(select);
                        if (this.lowestLevelByDefault) {
                            $select.find('option').last().prop('selected', true);
                        } else {
                            var setFirst = true;
                            var action = $select.data('role-action');
                            var defaultLevel = null;
                            if (action) defaultLevel = this.defaultLevels[action];
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
                    }.bind(this));
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

            this.scopeList.forEach(function (scope) {
                var o = {};

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

                if (this.aclTypeMap[scope] != 'boolean') {
                    this.actionList.forEach(function (action, j) {
                        var allowedActionList = this.getMetadata().get(['scopes', scope, this.type + 'ActionList']);

                        if (allowedActionList) {
                            if (!~allowedActionList.indexOf(action)) {
                                list.push({
                                    action: action,
                                    levelList: false,
                                    level: null
                                });
                                return;
                            }
                        }

                        if (action === 'stream') {
                            if (!this.getMetadata().get('scopes.' + scope + '.stream')) {
                                list.push({
                                    action: 'stream',
                                    levelList: false,
                                    level: null
                                });
                                return;
                            }
                        }

                        var level = 'no';
                        if (~this.booleanActionList.indexOf(action)) {
                            level = 'no';
                        }
                        if (scope in aclData) {
                            if (access == 'enabled') {
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
                            levelList: levelList
                        });
                    }, this);
                }

                aclDataList.push({
                    list: list,
                    access: access,
                    name: scope,
                    type: type
                });
            }, this);

            return aclDataList;
        },

        setup: function () {
            this.mode = this.options.mode || 'detail';

            this.final = this.options.final || false;

            this.setupData();

            this.listenTo(this.model, 'change', function () {
                if (this.model.hasChanged('data') || this.model.hasChanged('fieldData')) {
                    this.setupData();
                }
            }, this);

            this.listenTo(this.model, 'sync', function () {
                this.setupData();
                if (this.isRendered()) {
                    this.reRender();
                }
            }, this);

            this.template = 'role/table';
            if (this.mode == 'edit') {
                this.template = 'role/table-edit';
            }

            this.once('remove', function () {
                $(window).off('scroll.scope-' + this.cid);
                $(window).off('resize.scope-' + this.cid);
                $(window).off('scroll.field-' + this.cid);
                $(window).off('resize.field-' + this.cid);
            }, this);
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

            var scopeListAll = Object.keys(this.getMetadata().get('scopes')).sort(function (v1, v2) {
                 return this.translate(v1, 'scopeNamesPlural').localeCompare(this.translate(v2, 'scopeNamesPlural'));
            }.bind(this));

            scopeListAll.forEach(function (scope) {
                if (this.getMetadata().get('scopes.' + scope + '.disabled')) return;
                var acl = this.getMetadata().get('scopes.' + scope + '.acl');
                if (acl) {
                    this.scopeList.push(scope);
                    this.aclTypeMap[scope] = acl;
                    if (acl === true) {
                        this.aclTypeMap[scope] = 'record';
                    }
                }
            }, this);
        },

        setupFieldTableDataList: function () {
            this.fieldTableDataList = [];
            this.scopeList.forEach(function (scope) {
                var d = this.getMetadata().get('scopes.' + scope) || {};
                if (!d.entity) return;

                if (!(scope in this.acl.fieldData)) {
                    if (this.mode === 'edit') {
                        this.fieldTableDataList.push({
                            name: scope,
                            list: []
                        });
                        return;
                    }
                    return;
                };
                var scopeData = this.acl.fieldData[scope];

                var fieldList = this.getFieldManager().getEntityTypeFieldList(scope);
                this.getLanguage().sortFieldList(scope, fieldList);

                var fieldDataList = [];
                fieldList.forEach(function (field) {
                    if (!(field in scopeData)) return;

                    var list = [];

                    this.fieldActionList.forEach(function (action) {
                        list.push({
                            name: action,
                            value: scopeData[field][action] || 'yes'
                        })
                    }, this);

                    if (this.mode === 'detail') {
                        if (!list.length) return;
                    }
                    fieldDataList.push({
                        name: field,
                        list: list
                    });
                }, this);


                this.fieldTableDataList.push({
                    name: scope,
                    list: fieldDataList
                });
            }, this);
        },

        fetchScopeData: function () {
            var data = {};

            var scopeList = this.scopeList;
            var actionList = this.actionList;
            var aclTypeMap = this.aclTypeMap;

            for (var i in scopeList) {
                var scope = scopeList[i];
                if (this.$el.find('select[name="' + scope + '"]').val() == 'not-set') {
                    continue;
                }
                if (this.$el.find('select[name="' + scope + '"]').val() == 'disabled') {
                    data[scope] = false;
                } else {
                    var o = true;
                    if (aclTypeMap[scope] != 'boolean') {
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
            this.fieldTableDataList.forEach(function (scopeData) {
                var scopeObj = {};
                var scope = scopeData.name;
                scopeData.list.forEach(function (fieldData) {
                    var field = fieldData.name;
                    var fieldObj = {};
                    this.fieldActionList.forEach(function (action) {
                        var $select = this.$el.find('select[data-scope="'+scope+'"][data-field="'+field+'"][data-action="'+action+'"]');
                        if (!$select.length) return;
                        fieldObj[action] = $select.val();
                    }, this);
                    scopeObj[field] = fieldObj;
                }, this);
                data[scope] = scopeObj;
            }, this);

            return data;
        },

        afterRender: function () {
            if (this.mode == 'edit') {
                this.scopeList.forEach(function (scope) {
                    var $read = this.$el.find('select[name="'+scope+'-read"]');
                    $read.on('change', function () {
                        var value = $read.val();
                        this.controlEditSelect(scope, value);
                        this.controlDeleteSelect(scope, value);
                        this.controlStreamSelect(scope, value);
                    }.bind(this));

                    var $edit = this.$el.find('select[name="'+scope+'-edit"]');
                    $edit.on('change', function () {
                        var value = $edit.val();
                        this.controlDeleteSelect(scope, value);
                    }.bind(this));

                    this.controlEditSelect(scope, $read.val(), true);
                    this.controlStreamSelect(scope, $read.val(), true);
                    this.controlDeleteSelect(scope, $edit.val(), true);
                }, this);

                this.fieldTableDataList.forEach(function (o) {
                    var scope = o.name;
                    o.list.forEach(function (f) {
                        var field = f.name;

                        var $read = this.$el.find('select[data-scope="'+scope+'"][data-field="'+field+'"][data-action="read"]');
                        $read.on('change', function () {
                            var value = $read.val();
                            this.controlFieldEditSelect(scope, field, value);
                        }.bind(this));

                        this.controlFieldEditSelect(scope, field, $read.val(), true);
                    }, this);

                }, this);

                this.initStickyHeader('scope');
                this.initStickyHeader('field');

                this.setSelectColors();
            }
        },

        controlFieldEditSelect: function (scope, field, value, dontChange) {
            var $edit = this.$el.find('select[data-scope="'+scope+'"][data-field="'+field+'"][data-action="edit"]');

            if (!dontChange) {
                if (this.fieldLevelList.indexOf($edit.val()) < this.fieldLevelList.indexOf(value)) {
                    $edit.val(value);
                }
            }

            $edit.find('option').each(function (i, o) {
                var $o = $(o);
                if (this.fieldLevelList.indexOf($o.val()) < this.fieldLevelList.indexOf(value)) {
                    $o.attr('disabled', 'disabled');
                } else {
                    $o.removeAttr('disabled');
                }
            }.bind(this));

            this.controlSelectColor($edit);
        },

        controlEditSelect: function (scope, value, dontChange) {
            var $edit = this.$el.find('select[name="'+scope+'-edit"]');

            if (!dontChange) {
                if (this.levelList.indexOf($edit.val()) < this.levelList.indexOf(value)) {
                    $edit.val(value);
                }
            }

            $edit.find('option').each(function (i, o) {
                var $o = $(o);
                if (this.levelList.indexOf($o.val()) < this.levelList.indexOf(value)) {
                    $o.attr('disabled', 'disabled');
                } else {
                    $o.removeAttr('disabled');
                }
            }.bind(this));

            this.controlSelectColor($edit);
        },

        controlStreamSelect: function (scope, value, dontChange) {
            var $stream = this.$el.find('select[name="'+scope+'-stream"]');

            if (!dontChange) {
                if (this.levelList.indexOf($stream.val()) < this.levelList.indexOf(value)) {
                    $stream.val(value);
                }
            }

            $stream.find('option').each(function (i, o) {
                var $o = $(o);
                if (this.levelList.indexOf($o.val()) < this.levelList.indexOf(value)) {
                    $o.attr('disabled', 'disabled');
                } else {
                    $o.removeAttr('disabled');
                }
            }.bind(this));

            this.controlSelectColor($stream);
        },

        controlDeleteSelect: function (scope, value, dontChange) {
            var $delete = this.$el.find('select[name="'+scope+'-delete"]');

            if (!dontChange) {
                if (this.levelList.indexOf($delete.val()) < this.levelList.indexOf(value)) {
                    $delete.val(value);
                }
            }

            $delete.find('option').each(function (i, o) {
                var $o = $(o);
                if (this.levelList.indexOf($o.val()) < this.levelList.indexOf(value)) {
                    $o.attr('disabled', 'disabled');
                } else {
                    $o.removeAttr('disabled');
                }
            }.bind(this));

            this.controlSelectColor($delete);
        },

        showAddFieldModal: function (scope) {
            this.trigger('change');

            var ignoreFieldList = Object.keys(this.acl.fieldData[scope] || {});

            this.createView('addField', 'views/role/modals/add-field', {
                scope: scope,
                ignoreFieldList: ignoreFieldList,
                type: this.type
            }, function (view) {
                view.render();

                this.listenTo(view, 'add-field', function (field) {
                    view.close();

                    this.fieldTableDataList.forEach(function (scopeData) {
                        if (scopeData.name !== scope) return;
                        var found = false;
                        scopeData.list.forEach(function (d) {
                            if (d.name === field) {
                                found = true;
                            }
                        }, this);
                        if (found) return;
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
                    }, this);

                    this.reRender();
                }, this);
            }, this);
        },

        removeField: function (scope, field) {
            this.trigger('change');

            this.fieldTableDataList.forEach(function (scopeData) {
                if (scopeData.name !== scope) return;
                var index = -1;
                scopeData.list.forEach(function (d, i) {
                    if (d.name === field) {
                        index = i;
                    }
                }, this);
                if (~index) {
                    scopeData.list.splice(index, 1);
                    this.reRender();
                };

            }, this);
        },

        initStickyHeader: function (type) {
            var $sticky = this.$el.find('.sticky-header-' + type);
            var $window = $(window);

            var screenWidthXs = this.getThemeManager().getParam('screenWidthXs');

            var $buttonContainer = $('.detail-button-container');

            var $table = this.$el.find('table.'+type+'-level');

            if (!$buttonContainer.length) return;

            var handle = function (e) {
                if ($(window.document).width() < screenWidthXs) {
                    $sticky.addClass('hidden');
                    return;
                }
                var stickTopPosition = $buttonContainer.get(0).getBoundingClientRect().top + $buttonContainer.outerHeight();


                var topEdge = $table.position().top;
                topEdge += $buttonContainer.height();
                topEdge += $table.find('tr > th').height();

                var bottomEdge = topEdge + $table.outerHeight(true);

                var scrollTop = $window.scrollTop();

                var width = $table.width();

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
            }.bind(this);


            $window.off('scroll.' + type + '-' + this.cid);
            $window.on('scroll.' + type + '-' + this.cid, handle);

            $window.off('resize.' + type + '-' + this.cid);
            $window.on('resize.' + type + '-' + this.cid, handle);
        },

        setSelectColors: function () {
            this.$el.find('select[data-type="access"]').each(function (i, el) {
                var $select = $(el);
                this.controlSelectColor($select);
            }.bind(this));

            this.$el.find('select.scope-action').each(function (i, el) {
                var $select = $(el);
                this.controlSelectColor($select);
            }.bind(this));

            this.$el.find('select.field-action').each(function (i, el) {
                var $select = $(el);
                this.controlSelectColor($select);
            }.bind(this));
        },

        controlSelectColor: function ($select) {
            var level = $select.val();
            var color = this.colors[level] || '';

            if (level === 'not-set') color = '';
            $select.css('color', color);

            $select.children().each(function (j, el) {
                var $o = $(el);
                var level = $o.val();

                var color = this.colors[level] || '';
                if (level === 'not-set') color = '';

                if ($o.attr('disabled')) {
                    color = '';
                }

                $o.css('color', color);
            }.bind(this));
        },
    });
});
