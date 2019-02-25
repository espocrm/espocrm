/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('views/role/record/edit', 'views/record/edit', function (Dep) {

    return Dep.extend({

        tableView: 'views/role/record/table',

        sideView: false,

        isWide: true,

        columnCount: 3,

        stickButtonsContainerAllTheWay: true,

        fetch: function () {
            var data = Dep.prototype.fetch.call(this);

            data['data'] = {};

            var scopeList = this.getView('extra').scopeList;
            var actionList = this.getView('extra').actionList;
            var aclTypeMap = this.getView('extra').aclTypeMap;

            for (var i in scopeList) {
                var scope = scopeList[i];
                if (this.$el.find('select[name="' + scope + '"]').val() == 'not-set') {
                    continue;
                }
                if (this.$el.find('select[name="' + scope + '"]').val() == 'disabled') {
                    data['data'][scope] = false;
                } else {
                    var o = true;
                    if (aclTypeMap[scope] != 'boolean') {
                        o = {};
                        for (var j in actionList) {
                            var action = actionList[j];
                            o[action] = this.$el.find('select[name="' + scope + '-' + action + '"]').val();
                        }
                    }
                    data['data'][scope] = o;
                }
            }

            data['data'] = this.getView('extra').fetchScopeData();
            data['fieldData'] = this.getView('extra').fetchFieldData();

            return data;
        },

        getDetailLayout: function (callback) {
            var simpleLayout = [
                {
                    label: '',
                    cells: [
                        {
                            name: 'name',
                            type: 'varchar',
                        },
                    ]
                }
            ];
            callback({
                type: 'record',
                layout: this._convertSimplifiedLayout(simpleLayout)
            });
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.createView('extra', this.tableView, {
                mode: 'edit',
                el: this.options.el + ' .extra',
                model: this.model
            }, function (view) {
                this.listenTo(view, 'change', function () {
                    var data = this.fetch();
                    this.model.set(data);
                }, this);
            }, this);
        }

    });
});


