/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

Espo.define('views/role/record/table', 'view', function (Dep) {

    return Dep.extend({

        template: 'role/table',

        scopeList: null,

        actionList: ['read', 'stream', 'edit', 'delete'],

        accessList: ['not-set', 'enabled', 'disabled'],

        colors: {
            all: '#6BC924',
            team: '#999900',
            own: '#CC9900',
            no: '#F23333',

            enabled: '#6BC924',
            disabled: '#F23333',
            'not-set': '#A8A8A8',
        },

        mode: 'detail',

        aclData: null,

        levelListMap: {
            'recordAllTeamOwnNo': ['all', 'team', 'own', 'no'],
            'recordAllTeamNo': ['all', 'team', 'no'],
            'recordAllOwnNo': ['all', 'own', 'no'],
            'recordAllNo': ['all', 'no'],
            'record': ['all', 'team', 'own', 'no'],
        },

        data: function () {
            var data = {};
            data['editMode'] = this.mode === 'edit';
            data['actionList'] = this.actionList;
            data['accessList'] = this.accessList;
            data['colors'] = this.colors;
            data['aclTable'] = this.getAclTable();
            return data;
        },

        getAclTable: function () {
            var aclData = this.aclData;
            var aclTable = {};
            for (var i in this.scopeList) {
                var controller = this.scopeList[i];
                var o = {};

                var access = 'not-set';

                if (this.final) {
                    access = 'enabled';
                }

                if (controller in aclData) {
                    if (aclData[controller] === false) {
                        access = 'disabled';
                    } else {
                        access = 'enabled';
                    }
                }
                if (this.aclTypeMap[controller] != 'boolean') {
                    for (var j in this.actionList) {
                        var action = this.actionList[j];
                        var level = 'all';
                        if (controller in aclData) {
                            if (access == 'enabled') {
                                if (aclData[controller] !== true) {
                                    if (action in aclData[controller]) {
                                        level = aclData[controller][action];
                                    } else {
                                        // TODO remove it
                                        if (j > 0) {
                                            level = o[this.actionList[j - 1]].level;
                                        }
                                    }
                                }
                            } else {
                                level = 'no';
                            }
                        }
                        o[action] = {level: level, name: controller + '-' + action};
                    }
                }
                var type = this.aclTypeMap[controller];
                aclTable[controller] = {
                    acl: o,
                    access: access,
                    name: controller,
                    type: type,
                    levelList: this.levelListMap[type] || []
                };
            }
            return aclTable;
        },

        getAclList: function (type) {

        },

        setup: function () {
            this.mode = this.options.mode || 'detail';
            this.aclData = this.options.aclData;
            this.final = this.options.final || false;

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
                        this.aclTypeMap[scope] = 'recordAllTeamOwnNo';
                    }
                }
            }, this);

            this.listenTo(this.model, 'sync', function () {
                if (this.isRendered()) {
                    this.reRender();
                }
            }, this);
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
            }
        },

        controlEditSelect: function (scope, value, dontChange) {
            var $edit = this.$el.find('select[name="'+scope+'-edit"]');

            if (!dontChange) {
                if (this.levelListMap.record.indexOf($edit.val()) < this.levelListMap.record.indexOf(value)) {
                    $edit.val(value);
                }
            }

            $edit.find('option').each(function (i, o) {
                var $o = $(o);
                if (this.levelListMap.record.indexOf($o.val()) < this.levelListMap.record.indexOf(value)) {
                    $o.attr('disabled', 'disabled');
                } else {
                    $o.removeAttr('disabled');
                }
            }.bind(this));
        },

        controlStreamSelect: function (scope, value, dontChange) {
            var $stream = this.$el.find('select[name="'+scope+'-stream"]');

            if (!dontChange) {
                if (this.levelListMap.record.indexOf($stream.val()) < this.levelListMap.record.indexOf(value)) {
                    $stream.val(value);
                }
            }

            $stream.find('option').each(function (i, o) {
                var $o = $(o);
                if (this.levelListMap.record.indexOf($o.val()) < this.levelListMap.record.indexOf(value)) {
                    $o.attr('disabled', 'disabled');
                } else {
                    $o.removeAttr('disabled');
                }
            }.bind(this));
        },

        controlDeleteSelect: function (scope, value, dontChange) {
            var $delete = this.$el.find('select[name="'+scope+'-delete"]');

            if (!dontChange) {
                if (this.levelListMap.record.indexOf($delete.val()) < this.levelListMap.record.indexOf(value)) {
                    $delete.val(value);
                }
            }

            $delete.find('option').each(function (i, o) {
                var $o = $(o);
                if (this.levelListMap.record.indexOf($o.val()) < this.levelListMap.record.indexOf(value)) {
                    $o.attr('disabled', 'disabled');
                } else {
                    $o.removeAttr('disabled');
                }
            }.bind(this));
        },
    });
});


