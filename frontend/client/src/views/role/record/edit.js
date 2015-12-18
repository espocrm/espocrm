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

Espo.define('views/role/record/edit', 'views/record/edit', function (Dep) {

    return Dep.extend({

        sideView: 'views/role/record/detail-side',

        events: _.extend({
            'change select[data-type="access"]': function (e) {
                var scope = $(e.target).attr('name');
                var $dropdowns = this.$el.find('select[data-scope="' + scope + '"]');

                if ($(e.target).val() == 'enabled') {
                    $dropdowns.removeAttr('disabled');
                } else {
                    $dropdowns.attr('disabled', 'disabled');
                }
            }
        }, Dep.prototype.events),

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

            data['data'] = data['data'];
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

            this.createView('extra', 'views/role/record/table', {
                mode: 'edit',
                acl: {
                    data: this.model.get('data') || {},
                    fieldData: this.model.get('fieldData') || {}
                },
                el: this.options.el + ' .extra'
            });
        },
    });
});


